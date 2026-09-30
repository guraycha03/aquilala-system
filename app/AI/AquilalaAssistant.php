<?php

namespace App\AI;

use App\AI\Prompts\AquilalaAssistantPrompt;
use App\Models\User;
use App\Rules\CropRecommendationEngine;
use App\Services\FarmingKnowledgeService;
use Illuminate\Support\Facades\Log;

/**
 * AquilalaAssistant
 *
 * The pipeline, in the order the rules require:
 *
 *   USER
 *     -> INTENT ROUTER          (AiIntentRouter, in PHP, no model)
 *     -> LARAVEL SERVICE        (AiContextBuilder over ProductSearchService,
 *                                CropRecommendationEngine, FarmingKnowledgeService)
 *     -> DATABASE
 *     -> VERIFIED CONTEXT
 *     -> LLM EXPLANATION        (AiLlmClient, explanation only)
 *     -> AI RESPONSE VALIDATOR  (AiResponseValidator)
 *     -> FRONTEND
 *
 * Two properties hold no matter what the model does:
 *
 *   - Every response has a deterministic answer assembled in PHP. The model
 *     only ever replaces that wording with better wording. If the provider is
 *     down, the key is missing, or the output is unusable, the deterministic
 *     answer is what the farmer sees.
 *   - The product payload in the response is built from `products` rows. The
 *     model selects which retrieved products to discuss; it cannot add one,
 *     change a price, or supply a link.
 */
class AquilalaAssistant
{
    public function __construct(
        private readonly AiIntentRouter $router,
        private readonly AiContextBuilder $contextBuilder,
        private readonly AiResponseValidator $validator,
        private readonly AiLlmClient $llm,
        private readonly FarmingKnowledgeService $knowledge,
    ) {
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{
     *     reply: string,
     *     type: string,
     *     suggestions: array<int, array<string, mixed>>,
     *     context: array<string, mixed>,
     *     intent: string,
     *     source: string,
     *     violations: array<int, string>
     * }
     */
    public function handle(
        string $message,
        ?User $user = null,
        array $history = [],
        ?array $conversationContext = null,
    ): array {
        $context = AiConversationContext::fromArray($conversationContext);
        $routing = $this->router->route($message, $context, $history);
        $intent = $routing['intent'];

        $contextData = $this->contextBuilder->build($message, $routing, $user, $context);
        $fallback = $this->deterministicAnswer($intent, $contextData, $routing);

        // A turn the database cannot answer is answered by Laravel. Two
        // situations, and both are refusals rather than turns:
        //
        //   - the intent has no model involvement at all (a clarification, an
        //     off-topic question, an approved-knowledge answer);
        //   - a required input is missing, so there is nothing to explain yet.
        //     The model is not asked to fill the gap, because "how much
        //     fertilizer do I need" with no farm area has no answer, and a
        //     plausible one is exactly the failure this pipeline exists to
        //     prevent.
        $skipModel = AiIntent::isDeterministicOnly($intent)
            || $contextData['quotation'] === null && $intent === AiIntent::QUOTATION
            || $contextData['missing'] !== []
            || ($contextData['products'] === [] && $this->hasNoNarrative($contextData));

        $violations = [];
        $source = 'fallback';

        if ($skipModel) {
            $reply = $fallback;
        } else {
            $result = $this->explain($message, $intent, $contextData, $user, $fallback);

            $reply = $result['answer'];
            $violations = $result['violations'];
            $source = $result['source'];
        }

        $nextContext = $context->withResolved(
            intent: $intent,
            crop: $routing['slots']['crop'] ?? $context->lastCrop,
            productIds: $contextData['product_ids'],
            season: $routing['slots']['season'] ?? $context->lastSeason,
            area: $routing['slots']['area'] ?? $context->lastArea,
        );

        $this->log($user, $intent, $contextData, $source, $violations);

        return [
            'reply'      => $reply,
            'type'       => $this->responseType($intent, $contextData),
            // Product cards, built from database rows. The frontend renders
            // these; it is never asked to parse them out of prose.
            'suggestions' => $contextData['products'],
            'context'    => $nextContext->toArray(),
            'intent'     => $intent,
            'source'     => $source,
            'violations' => $violations,
        ];
    }

    // ────────────────────────────────────────────────────────────
    //  Model step
    // ────────────────────────────────────────────────────────────

    /**
     * Ask the model to explain the verified context, then hold the result
     * against it.
     *
     * @param  array<string, mixed>  $contextData
     * @return array{answer: string, violations: array<int, string>, source: string}
     */
    private function explain(
        string $message,
        string $intent,
        array $contextData,
        ?User $user,
        string $fallback,
    ): array {
        $completion = $this->llm->completeJson([
            ['role' => 'system', 'content' => AquilalaAssistantPrompt::system($user?->role)],
            ['role' => 'user', 'content' => AquilalaAssistantPrompt::user($contextData, $message)],
        ]);

        if ($completion === null) {
            return ['answer' => $fallback, 'violations' => [], 'source' => 'fallback'];
        }

        $decoded = $this->validator->parse($completion['raw']);

        if ($decoded === null) {
            return [
                'answer'     => $fallback,
                'violations' => [AiResponseValidator::VIOLATION_MALFORMED],
                'source'     => 'fallback',
            ];
        }

        $validated = $this->validator->validate(
            $decoded,
            $contextData['products'],
            $fallback,
            $this->trustedCropNames($contextData),
        );

        $answer = $validated['answer'] !== '' ? $validated['answer'] : $fallback;

        // Laravel's wording wins outright on two grounds. A clarification the
        // model wrote is never used, because the router's question is the more
        // precise of the two and a reworded question is a way to slip an
        // assumption in. And any violation at all — an invented product, a
        // price that disagrees with the database — costs the farmer the model's
        // phrasing and nothing else.
        if ($validated['needs_clarification'] || $validated['violations'] !== []) {
            $answer = $fallback;
        }

        return [
            'answer'     => $answer,
            'violations' => $validated['violations'],
            'source'     => 'ai',
        ];
    }

    // ────────────────────────────────────────────────────────────
    //  Deterministic answers
    // ────────────────────────────────────────────────────────────

    /**
     * Laravel's own answer, built from the retrieved data.
     *
     * This is the floor of the assistant: correct with no model, no network
     * and no key. It is also the wording used whenever the model's output is
     * rejected, so a hallucination costs the farmer eloquence, not accuracy.
     *
     * @param  array<string, mixed>  $contextData
     * @param  array{intent: string, slots: array<string, mixed>, follow_up: bool}  $routing
     */
    private function deterministicAnswer(string $intent, array $contextData, array $routing): string
    {
        $crop = $routing['slots']['crop'] ?? null;
        $products = $contextData['products'];
        $missing = $contextData['missing'] ?? [];

        // A comparison the system cannot make is answered before the intent's
        // own handler, because the intent was chosen from a word like "yield"
        // that has nothing to do with the data the handler would read.
        if (isset($routing['comparison']) && is_array($routing['comparison'])) {
            return $this->unrankedComparisonAnswer($routing['comparison']);
        }

        return match ($intent) {
            AiIntent::PRODUCT_SEARCH => $contextData['facts']['ambiguous_product_reference'] ?? null
                ? $this->ambiguousProductQuestion($contextData['facts']['ambiguous_product_reference'])
                : $this->searchAnswer($products, $crop, $routing),
            AiIntent::PRODUCT_RECOMMENDATION,
            AiIntent::FERTILIZER_ADVICE => $this->recommendationAnswer($products, $crop, $missing, $routing),
            AiIntent::COST_ESTIMATION => $this->costAnswer($contextData, $crop, $missing),
            AiIntent::QUOTATION => $this->quotationAnswer($contextData['quotation']),
            AiIntent::CROP_ADVICE => $this->cropAdviceAnswer($crop, $missing, $routing),
            AiIntent::GREETING => $this->greetingAnswer($contextData),
            AiIntent::CROP_DISCOVERY,
            AiIntent::CROP_SEARCH => $this->cropDiscoveryAnswer($contextData, $routing),
            AiIntent::CROP_RECOMMENDATION => $this->cropChoiceAnswer($contextData, $routing),
            AiIntent::SEASONAL_RECOMMENDATION => $this->seasonalAnswer($contextData, $routing),
            AiIntent::FARMING_ADVICE => $this->farmingAdviceAnswer($contextData, $crop, $missing, $routing),
            AiIntent::GENERAL_FARMING => $this->generalAnswer($contextData),
            AiIntent::SYSTEM_HELP => $this->helpAnswer($contextData),
            AiIntent::UNSUPPORTED => $this->unsupportedAnswer(),
            AiIntent::CLARIFICATION => $this->clarificationAnswer($missing, $routing),
            default => $this->clarificationAnswer($missing, $routing),
        };
    }

    /**
     * A greeting, in one line.
     *
     * "Hi" used to receive the full capability and restriction briefing, which
     * is the least useful thing to say to someone who has only said hello and
     * reads like a warning label. A greeting is a courtesy; there is nothing in
     * it worth a paragraph.
     *
     * @param  array<string, mixed>  $contextData
     */
    private function greetingAnswer(array $contextData): string
    {
        $season = $contextData['facts']['season_profile'] ?? [];

        $reply = 'Hi! How can I help with your farm today?';

        if (! empty($season['label'])) {
            $reply .= ' It is **' . $season['label'] . '** in Bulan right now'
                . (($season['sub_season'] ?? '') !== '' ? ' — ' . $season['sub_season'] : '')
                . '.';
        }

        return $reply;
    }

    /**
     * "What crops can I plant?", "what fruits does Aquilala support?"
     *
     * Every name here is a row in the crops directory. The categories are the
     * directory's own, which is why "fruits" resolves without a hardcoded list
     * of fruit names somewhere in a prompt.
     *
     * @param  array<string, mixed>  $contextData
     * @param  array{intent: string, slots: array<string, mixed>, follow_up: bool}  $routing
     */
    private function cropDiscoveryAnswer(array $contextData, array $routing): string
    {
        $crops = $contextData['facts']['crops'] ?? [];
        $category = $routing['slots']['crop_category']['name'] ?? null;

        if ($crops === []) {
            return "Aquilala's crop directory has nothing recorded under **{$category}**. "
                . 'Tell me the crop you mean and I will check what the Aquilala formulas and catalog hold for it.';
        }

        $heading = $category !== null
            ? '### ' . $category . ' in the Aquilala crop directory'
            : '### Crops in the Aquilala crop directory';

        $lines = [$heading, ''];

        if ($category === null) {
            $categories = $contextData['facts']['crop_categories'] ?? [];

            if ($categories !== []) {
                $lines[] = 'Aquilala knows ' . count($crops) . ' crops, in these categories: '
                    . implode(', ', $categories) . '.';
                $lines[] = '';
            }
        }

        foreach ($this->groupCropsByCategory($crops) as $groupCategory => $entries) {
            $names = array_map(static fn (array $crop) => (string) $crop['name'], $entries);

            $lines[] = '- **' . $groupCategory . ':** ' . implode(', ', $names);
        }

        $lines[] = '';
        $lines[] = 'Tell me which one you are planting and I will show what Aquilala has for it — the products, the formulas, and a cost estimate.';

        return implode("\n", $lines);
    }

    /**
     * "What fruit should I plant?", "suggest the most tasty fruit…"
     *
     * The subjective part is answered first and plainly. A model asked which
     * fruit is tastiest will produce a confident answer with no data behind it;
     * the honest answer is that taste is the farmer's to judge, followed by the
     * choices Aquilala actually supports.
     *
     * @param  array<string, mixed>  $contextData
     * @param  array{intent: string, slots: array<string, mixed>, follow_up: bool}  $routing
     */
    private function cropChoiceAnswer(array $contextData, array $routing): string
    {
        $crops = $contextData['facts']['crops'] ?? [];
        $subjective = (bool) ($contextData['facts']['preference_is_subjective'] ?? false);
        $category = $routing['slots']['crop_category']['name'] ?? null;

        // Neither a crop nor a kind of crop. The directory holds 29 crops, so
        // listing all of them is technically grounded and practically useless -
        // it is the same refusal by other means, with a longer response. One
        // short question is the honest answer to "what should I plant?".
        if (($routing['slots']['crop'] ?? null) === null && $category === null) {
            $backyard = (bool) ($contextData['facts']['is_backyard_question'] ?? false);

            $answer = $backyard
                ? "I can narrow this down to the crops Aquilala's current rules cover, but Aquilala has no "
                    . "backyard-specific rules — the association's formulas are written for field production, so I "
                    . "will not present them as if they were tuned for a backyard plot. Are you looking for "
                    . '**fruits**, **vegetables**, or **rice or corn**?'
                : 'Are you after a **fruit crop**, a **vegetable**, or a **rice or corn** planting? '
                    . 'The answer depends on which, and I would rather ask than guess.';

            return $answer;
        }

        if ($crops === []) {
            return "Aquilala's crop directory has nothing recorded for that. "
                . 'Tell me the crop or the kind of crop you mean and I will check what Aquilala holds for it.';
        }

        $lines = [];

        if ($subjective) {
            $lines[] = 'Taste is personal, so Aquilala does not rank its crops by taste — I will not invent a winner. '
                . 'What I can do is show you the ' . ($category !== null ? strtolower($category) . ' ' : '')
                . 'crops in the Aquilala directory so you can pick the ones that suit you.';
        } else {
            $lines[] = 'Aquilala lists ' . count($crops) . ($crops === 1 ? ' crop' : ' crops')
                . ($category !== null ? ' under **' . $category . '**' : '') . ':';
        }

        $lines[] = '';

        $covered = [];
        $listed = [];

        foreach ($crops as $crop) {
            $name = (string) $crop['name'];
            $hasRules = (bool) ($crop['has_formula'] ?? false);

            if ($hasRules) {
                $covered[] = $name;
            }

            // The directory listing says what exists. Whether Aquilala has a rule
            // for it is a separate sentence, not a column of counts - a farmer
            // cannot act on "4 Aquilala formulas", and a number in a list reads
            // as a ranking the system has no basis for.
            $lines[] = '- **' . $name . '**'
                . (($crop['category'] ?? null) !== null ? ' · ' . $crop['category'] : '')
                . ($hasRules ? ' · covered by Aquilala\'s current rules' : ' · no Aquilala rule yet');
        }

        if ($covered !== []) {
            $lines[] = '';

            // A backyard plot has no hectares to give, so asking for a farm area
            // there would be asking for a number the farmer cannot supply and
            // the rules were not written against anyway.
            $lines[] = ($contextData['facts']['is_backyard_question'] ?? false)
                ? 'If you tell me which of these you want to grow, I can show you what Aquilala carries for it. '
                    . 'Keep in mind these rules are written for field production, so I can describe the inputs but '
                    . 'not size them to a backyard plot.'
                : 'Give me the **farm area** and the **season** for any of those and I will run the recommendation and price it.';
        }

        $lines[] = '';
        $lines[] = '> **IMPORTANT:** I can tell you what Aquilala carries for these crops, but I do not rate taste, yield or quality for you.';

        return implode("\n", $lines);
    }

    /**
     * "What can I plant this October?"
     *
     * The month decides the season through the application's Bicol climate
     * calendar, and the season decides the crops through the formula matrix.
     * When the matrix is empty for that season the answer says so: an empty
     * answer from the data is an answer, and a plausible list is not.
     *
     * @param  array<string, mixed>  $contextData
     * @param  array{intent: string, slots: array<string, mixed>, follow_up: bool}  $routing
     */
    private function seasonalAnswer(array $contextData, array $routing): string
    {
        $window = $contextData['facts']['season_window'] ?? $this->currentWindow();
        $crops = $contextData['facts']['crops'] ?? [];

        $monthName = (string) ($window['month_name'] ?? 'this month');
        $season = (string) ($window['season'] ?? '');

        if ($crops === []) {
            return 'Aquilala does not have decision formulas recorded for any crop in the **' . $season . ' season** yet, '
                . 'so I cannot tell you what to plant in ' . $monthName . '. I will not guess a planting calendar. '
                . 'The formula matrix is maintained by the association, and I can show you what it does hold.';
        }

        $lines = [
            '### Planting in ' . $monthName,
            '',
            $monthName . ' falls in the **' . $season . ' season** in Bulan, Sorsogon. Here are the crops covered by '
                . 'Aquilala\'s ' . $season . '-season rules:',
            '',
        ];

        // No formula counts, and no ordering by them. "Rice 39, Corn 24" is the
        // application's internal coverage, not a judgement about the crops, and
        // a list ordered that way tells a farmer that Rice is better than Corn.
        foreach ($crops as $crop) {
            $lines[] = '- **' . $crop['name'] . '**'
                . (($crop['category'] ?? null) !== null ? ' · ' . $crop['category'] : '');
        }

        $lines[] = '';
        $lines[] = 'That is what Aquilala\'s rules cover for ' . $monthName . ' — I am not adding crops the matrix does not have, '
            . 'and I am not ranking them, because the association\'s rules do not rate one crop above another. '
            . 'Tell me which one and your **farm area**, and I will show the products and the cost.';

        return implode("\n", $lines);
    }

    /**
     * "How do I plant corn?"
     *
     * The crop's approved record, plus an honest statement of what Aquilala does
     * not hold. A planting guide with a spacing and a seed rate would be the most
     * useful thing in the conversation and the least defensible, because neither
     * number would have come from anywhere in this system.
     *
     * @param  array<string, mixed>  $contextData
     * @param  array<int, string>  $missing
     * @param  array{intent: string, slots: array<string, mixed>, follow_up: bool}  $routing
     */
    private function farmingAdviceAnswer(array $contextData, ?string $crop, array $missing, array $routing): string
    {
        if ($crop !== null) {
            $facts = $this->knowledge->factsFor($crop) ?? [];
            $steps = $this->knowledge->plantingSteps($crop, $facts['group'] ?? null);

            $lines = [];

            // General practice first, labelled as general. Then what Aquilala
            // actually holds, labelled as Aquilala's. Keeping the two apart is
            // the whole point - a farmer should never read a step from this
            // answer as though the association's rules said it.
            $lines[] = 'For general ' . strtolower((string) $crop) . ' planting, the usual process is:';
            $lines[] = '';

            foreach ($steps as $index => $step) {
                $lines[] = ($index + 1) . '. ' . $step;
            }

            $lines[] = '';
            $lines[] = 'That is general farming practice, not an Aquilala recommendation. '
                . 'Aquilala has no step-by-step guide for ' . $crop . ', so I am not giving you an exact planting distance, '
                . 'seed rate or spray schedule — those come from the association\'s decision formulas, which need your crop, '
                . 'season and farm area.';

            $base = $this->cropAdviceAnswer($crop, $missing, $routing);

            // The crop's own Aquilala record - maturity, seasons, local name -
            // still belongs in the answer. Only its guard-rail note is dropped,
            // because the note above already says the same thing once.
            $facts = array_values(array_filter(
                explode("\n", $base),
                static fn (string $line) => ! str_starts_with(trim($line), '> **IMPORTANT:**')
            ));

            if (array_filter($facts, static fn (string $line) => trim($line) !== '') !== []) {
                $lines[] = '';
                $lines[] = 'Aquilala\'s own record for ' . $crop . ':';
                $lines[] = '';
                $lines[] = implode("\n", $facts);
            }

            return implode("\n", $lines);
        }

        $crops = $contextData['facts']['crops'] ?? [];

        $lines = ['### What Aquilala can tell you about growing things', ''];

        if ($crops !== []) {
            $lines[] = 'Name the crop you are growing and I can give you its Aquilala record, plus the general planting process for it.';
            $lines[] = '';
            $lines[] = 'Crops in the directory: ' . implode(', ', array_slice(array_map(
                static fn (array $entry) => (string) $entry['name'],
                $crops
            ), 0, 15)) . '.';
        } else {
            $lines[] = 'Tell me the crop you are growing and I will give you its Aquilala record.';
        }

        $lines[] = '';
        $lines[] = '> **IMPORTANT:** I can give you general planting practice, but Aquilala has no step-by-step guide, '
            . 'so I will not give you an exact planting distance, seed rate or spray schedule. Those come from the '
            . 'association\'s decision formulas, which work from your crop, season and farm area.';

        return implode("\n", $lines);
    }

    /**
     * @return array{month: int, month_name: string, season: string}
     */
    private function currentWindow(): array
    {
        $now = now();

        return [
            'month'      => (int) $now->month,
            'month_name' => $now->format('F'),
            'season'     => \App\Helpers\SeasonHelper::getCurrentSeason($now),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $crops
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupCropsByCategory(array $crops): array
    {
        $grouped = [];

        foreach ($crops as $crop) {
            $category = ($crop['category'] ?? null) !== null && $crop['category'] !== ''
                ? (string) $crop['category']
                : 'Other';

            $grouped[$category][] = $crop;
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * The spec quotes this line in four places (#38 Test 12, #46, #47, #54), so
     * it is kept as one shared string rather than rebuilt per call site. #8 and
     * #28 word the same idea slightly differently; they agree on the meaning.
     */
    private function noProductMessage(?string $crop = null): string
    {
        $line = $crop !== null
            ? "I couldn't find that product in the current Aquilala catalog for **{$crop}**."
            : "I couldn't find that product in the current Aquilala catalog.";

        return $line . ' You can try another product name or ask me to show the available fertilizers.';
    }

    /**
     * @param  array{measure: string, subject: string}  $comparison
     */
    private function unrankedComparisonAnswer(array $comparison): string
    {
        $measure = $comparison['measure'];

        // Each of these says no to the comparison and yes to the thing that
        // can actually be done, because #45 is explicit that a safe assistant
        // which only says "I cannot" has failed the farmer just as badly as an
        // invented one.
        return match ($measure) {
            'yield' => "Aquilala does not currently have yield data that compares one crop against another, so I "
                . "cannot tell you which one yields most. What I can do is show you the crops the rules cover for "
                . "the season you are planting, and the typical yield Aquilala records for each one as a single "
                . "figure rather than a ranking. Tell me the **crop** and **season** and I will pull it up.",
            'profit' => "Aquilala does not currently track profit or returns for its crops, so I cannot tell you "
                . "which one earns most, and I will not guess at it. What the system can do is cost out a "
                . "planting: give me the **crop**, **season** and **farm area** and I will run the Aquilala "
                . "formulas and show the actual input cost.",
            'value' => "I can show you the **real price** of every product Aquilala lists, but the system has no "
                . "value score that would let me call one of them the best value, so I will not crown a winner. "
                . "Tell me the **crop** or the product and I will list what is in the catalog with its price and "
                . "availability.",
            default => "Aquilala does not have the data to compare those, so I will not pick one. Tell me the "
                . "**crop** and **season** and I will work from the data it does hold.",
        };
    }

    /**
     * "How much is it?" with two products in play.
     *
     * The specification's own example is "Do you mean YaraMila UNIK 16 or the
     * other fertilizer product?", so the names go in the question. Three
     * candidates would make a sentence nobody can read, so past that the
     * listing is the clearer answer.
     *
     * @param  array<int, string>  $names
     */
    private function ambiguousProductQuestion(array $names): string
    {
        if (count($names) < 2 || count($names) > 3) {
            return 'I found several products from the last answer. Which one do you mean?';
        }

        $first = array_shift($names);
        $last = array_pop($names);

        $options = array_merge([$first], $names, [$last]);

        return 'Do you mean ' . implode(', ', array_slice($options, 0, -1))
            . ' or ' . end($options) . '?';
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @param  array{intent: string, slots: array<string, mixed>, follow_up: bool}  $routing
     */
    private function searchAnswer(array $products, ?string $crop, array $routing): string
    {
        if ($products === []) {
            return $this->noProductMessage($crop);
        }

        $lines = [];

        if ($crop !== null) {
            $lines[] = "Aquilala lists " . count($products) . ($products === 1 ? ' product' : ' products') . " for **{$crop}**. Here " . ($products === 1 ? 'is the one' : 'are they') . ':';
        } else {
            $lines[] = "I found " . count($products) . ($products === 1 ? ' product' : ' products') . ' in the Aquilala catalog:';
        }

        foreach ($products as $product) {
            $lines[] = '- **' . $product['display_name'] . '** — ₱' . $product['price']
                . ' / ' . $product['price_unit'] . ' · ' . $product['stock']
                . ($product['store'] !== '' ? ' · ' . $product['store'] : '');
        }

        $lines[] = '';
        $lines[] = 'Open any product above to see the full details and place your inquiry with the store.';

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @param  array<int, string>  $missing
     * @param  array{intent: string, slots: array<string, mixed>, follow_up: bool}  $routing
     */
    private function recommendationAnswer(array $products, ?string $crop, array $missing, array $routing): string
    {
        $lines = [];

        if ($products !== []) {
            $season = $routing['slots']['season'] ?? null;
            $for = $season !== null ? ' in the ' . $season . ' season' : '';

            $lines[] = 'For **' . $crop . '**' . $for . ', the Aquilala formulas point to ' . count($products)
                . ($products === 1 ? ' product' : ' products') . ':';

            foreach ($products as $product) {
                $lines[] = '- **' . $product['display_name'] . '** — ₱' . $product['price']
                    . ' / ' . $product['price_unit'] . ' · ' . $product['stock'];
            }

            $lines[] = '';
        }

        $question = $this->clarificationQuestion($missing, $crop, $routing);

        if ($question !== null) {
            $lines[] = $question;
        } elseif ($products === []) {
            $lines[] = $this->noProductMessage($crop);
        }

        return implode("\n", array_filter($lines, static fn ($line) => $line !== '')) ?: 'I could not build a recommendation from the Aquilala data.';
    }

    /**
     * @param  array<string, mixed>  $contextData
     * @param  array<int, string>  $missing
     */
    private function costAnswer(array $contextData, ?string $crop, array $missing): string
    {
        $recommendation = $contextData['recommendation'] ?? null;

        if ($recommendation === null || ($recommendation['status'] ?? null) !== CropRecommendationEngine::STATUS_OK) {
            $question = $this->clarificationQuestion($missing, $crop, ['intent' => '', 'slots' => [], 'follow_up' => false]);

            if ($question !== null) {
                return $question;
            }

            return "I can't produce a cost estimate yet — Aquilala has no formula for **{$crop}**. "
                . 'The formula matrix is maintained by the association for the crops it supports.';
        }

        $summary = $recommendation['summary'];
        $area = $recommendation['area'];

        $lines = [
            '### Aquilala estimate for ' . $recommendation['crop'] . ' (' . $recommendation['season'] . ' season, ' . $area . ' ha)',
            '',
        ];

        foreach ($recommendation['items'] as $item) {
            $rate = $item['rate_per_hectare'] !== null
                ? number_format($item['rate_per_hectare'], 2) . ' ' . $item['unit'] . '/ha'
                : 'formula rate';

            $lines[] = '- **' . $item['product_name'] . '** — ' . $this->number($item['quantity']) . ' ' . $item['unit']
                . ' (' . $rate . ') at ₱' . number_format($item['price'], 2) . ' = **₱' . number_format($item['subtotal'], 2) . '**'
                . ($item['in_stock'] ? '' : ' · currently ' . strtolower($item['stock_status']));
        }

        $lines[] = '';
        $lines[] = '**Estimated total: ₱' . number_format((float) $summary['grand_total'], 2) . '** for '
            . $area . ' ha — about ₱' . number_format((float) $summary['grand_total'] / max((float) $area, 0.01), 2) . ' per hectare.';

        if ($recommendation['assumptions'] !== []) {
            $lines[] = '';
            foreach ($recommendation['assumptions'] as $assumption) {
                $lines[] = '*' . $assumption . '*';
            }
        }

        // The estimate stands on the assumption above, so the correction is part
        // of the answer rather than a separate question that replaces it.
        if (in_array('season', $missing, true)) {
            $lines[] = '';
            $lines[] = 'Are you planting in the **wet season** or the **dry season**? '
                . 'The rates differ, so tell me and I will confirm the number.';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>|null  $quotation
     */
    private function quotationAnswer(?array $quotation): string
    {
        if ($quotation === null) {
            return "You don't have a cost estimate in Aquilala yet. Open **Cost Estimates** to generate one from your crop, season and farm area — then ask me about it here.";
        }

        $lines = [
            '### Your latest estimate — ' . $quotation['quotation_number'],
            '',
            '- **Crop:** ' . $quotation['crop'] . ' (' . $quotation['season'] . ' season)',
            '- **Area:** ' . $quotation['land_area'] . ' ha',
            '- **Estimated total:** ₱' . number_format($quotation['total'], 2),
        ];

        if ($quotation['valid_until'] !== null) {
            $lines[] = '- **Valid until:** ' . $quotation['valid_until'];
        }

        $lines[] = '';
        $lines[] = 'Open **Cost Estimates** for the itemized breakdown. These figures come from the Aquilala formulas and live catalog prices.';

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, string>  $missing
     * @param  array{intent: string, slots: array<string, mixed>, follow_up: bool}  $routing
     */
    private function cropAdviceAnswer(?string $crop, array $missing, array $routing): string
    {
        $question = $this->clarificationQuestion($missing, $crop, $routing);

        if ($question !== null) {
            return $question;
        }

        $facts = $this->knowledge->factsFor($crop);

        if ($facts === null) {
            return "I don't have an Aquilala reference for **{$crop}**. "
                . 'I can answer from the approved Bulan, Sorsogon crop catalogue, so tell me which crop you mean.';
        }

        $lines = ['### ' . $facts['crop'] . ' in Bulan, Sorsogon', ''];

        if (! empty($facts['local_name'])) {
            $lines[] = $facts['local_name'];
        }

        $lines[] = '- **Group:** ' . $facts['group'];
        $lines[] = '- **Typical yield:** ' . $facts['typical_yield_per_ha'] . ' per hectare';
        $lines[] = '- **Maturity:** about ' . $facts['maturity_days'] . ' days from planting to harvest';
        $lines[] = '- **Seasons:** ' . implode(', ', $facts['seasons']);

        $lines[] = '';
        $lines[] = '> **IMPORTANT:** I do not give fertilizer rates, planting distances or pesticide schedules on my own. '
            . 'Aquilala calculates those from its formulas — tell me your farm area and season and I will run them for you.';

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $contextData
     */
    private function generalAnswer(array $contextData): string
    {
        $facts = $contextData['facts'] ?? [];
        $season = $facts['current_season'] ?? [];

        $lines = [
            '### What I can help with',
            '',
            '- Search the **Aquilala product catalog** and show what is actually listed, with live prices and stock.',
            '- Explain the recommendation the **Aquilala rule engine** produces for a crop and season.',
            '- Explain a **cost estimate** Aquilala calculated, and what each line is for.',
            '- Explain the approved **crop and season guidance** for Bulan, Sorsogon.',
        ];

        if (! empty($season['label'])) {
            $lines[] = '';
            $lines[] = 'Right now it is **' . $season['label'] . '** (' . ($season['sub_season'] ?? '') . ') — ' . ($season['typical_weather'] ?? '');
        }

        $lines[] = '';
        $lines[] = 'Tell me your **crop**, **season** and **farm area** and I will work from the Aquilala data.';

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $contextData
     */
    private function helpAnswer(array $contextData): string
    {
        $crops = $contextData['facts']['supported_crops'] ?? [];

        $lines = [
            '### I am the Aquilala farm assistant',
            '',
            'I answer from this system\'s own data — the product catalog, the decision formulas and the cost engine. '
                . 'I explain what they produce; I do not make up products, prices or dosages.',
            '',
            '**Ask me things like:**',
            '',
            '- "Do you have urea products?"',
            '- "What fertilizers for rice?"',
            '- "How much would 2 hectares of corn cost?"',
            '- "Can I review it?"',
            '- "How do I plant corn?"',
        ];

        if ($crops !== []) {
            $lines[] = '';
            $lines[] = 'Crops with Aquilala formulas: ' . implode(', ', $crops) . '.';
        }

        $lines[] = '';
        $lines[] = 'I cannot change prices or stock, create products, place orders, or browse the web.';

        return implode("\n", $lines);
    }

    private function unsupportedAnswer(): string
    {
        return "I focus on Aquilala farming and this system — the product catalog, the recommendation formulas, "
            . "and the cost estimates Aquilala calculates. I can't help with that one. "
            . 'Try asking what products you need for a crop, or what a cost estimate came to.';
    }

    /**
     * @param  array<int, string>  $missing
     * @param  array{intent: string, slots: array<string, mixed>, follow_up: bool}  $routing
     */
    private function clarificationAnswer(array $missing, array $routing): string
    {
        $question = $this->clarificationQuestion($missing, $routing['slots']['crop'] ?? null, $routing);

        return $question ?? 'How can I help with Aquilala farming? Tell me your **crop**, **season** and **farm area**.';
    }

    /**
     * One precise question about the single most useful missing fact.
     *
     * Crop first: without a crop nothing else can be looked up, so asking for
     * hectares from a farmer who has not said what they are growing only moves
     * the blockage. Then the area, then the season, so the farmer is never
     * asked for two things at once.
     *
     * @param  array<int, string>  $missing
     * @param  array{intent: string, slots: array<string, mixed>, follow_up: bool}  $routing
     */
    private function clarificationQuestion(array $missing, ?string $crop, array $routing): ?string
    {
        $area = $routing['slots']['area'] ?? null;

        if (in_array('crop', $missing, true)) {
            return 'Which crop are you asking about — for example rice, corn, or vegetables?';
        }

        if (in_array('crop_category', $missing, true)) {
            return 'Are you after a **fruit crop**, a **vegetable**, or a **rice or corn** planting?';
        }

        if (in_array('area', $missing, true) || ($area === null && $this->wantsArea($routing['intent']))) {
            return 'I can help work that out for you. What is your farm area in hectares?';
        }

        if (in_array('season', $missing, true)) {
            return 'I can find the products for **' . $crop . '**. Are you planting in the **wet season** or the **dry season**?';
        }

        return null;
    }

    /**
     * @param  array{intent: string, slots: array<string, mixed>, follow_up: bool}  $routing
     */
    private function wantsArea(string $intent): bool
    {
        return in_array($intent, [AiIntent::COST_ESTIMATION, AiIntent::PRODUCT_RECOMMENDATION, AiIntent::FERTILIZER_ADVICE], true);
    }

    // ────────────────────────────────────────────────────────────
    //  Helpers
    // ────────────────────────────────────────────────────────────

    /**
     * Crop names the verified context supplied, so the model may name a crop
     * Aquilala actually knows without the invented-product check objecting to a
     * word the application itself put in the prompt.
     *
     * @param  array<string, mixed>  $contextData
     * @return array<int, string>
     */
    private function trustedCropNames(array $contextData): array
    {
        $names = [];

        foreach ($contextData['facts']['crops'] ?? [] as $crop) {
            if (isset($crop['name'])) {
                $names[] = (string) $crop['name'];
            }
        }

        if (isset($contextData['facts']['crop']['crop'])) {
            $names[] = (string) $contextData['facts']['crop']['crop'];
        }

        return $names;
    }

    /**
     * @param  array<string, mixed>  $contextData
     */
    private function responseType(string $intent, array $contextData): string
    {
        if ($intent === AiIntent::CLARIFICATION) {
            return 'clarification';
        }

        if ($intent === AiIntent::UNSUPPORTED) {
            return 'unsupported';
        }

        if ($contextData['missing'] !== []) {
            return 'clarification';
        }

        if ($contextData['recommendation'] !== null) {
            return 'cost_estimate';
        }

        if ($contextData['quotation'] !== null) {
            return 'cost_estimate';
        }

        if ($contextData['products'] === [] && in_array($intent, [AiIntent::PRODUCT_SEARCH, AiIntent::PRODUCT_RECOMMENDATION], true)) {
            return 'no_results';
        }

        // A crop question with an empty directory is a real answer, not a
        // failure: "Aquilala holds nothing under Root Tubers" is worth saying
        // plainly, and "no_results" is how the frontend knows to keep the prose
        // rather than reaching for a product carousel.
        if (($contextData['facts']['crops'] ?? null) === []
            && in_array($intent, [AiIntent::CROP_DISCOVERY, AiIntent::CROP_SEARCH, AiIntent::CROP_RECOMMENDATION, AiIntent::SEASONAL_RECOMMENDATION], true)) {
            return 'no_results';
        }

        if ($contextData['products'] !== []) {
            return 'product_results';
        }

        return 'answer';
    }

    /**
     * True when the turn has no prose for the model to work on: nothing
     * retrieved, no rule result, no quotation, nothing in the facts.
     *
     * @param  array<string, mixed>  $contextData
     */
    private function hasNoNarrative(array $contextData): bool
    {
        if ($contextData['recommendation'] !== null || $contextData['quotation'] !== null) {
            return false;
        }

        $facts = $contextData['facts'] ?? [];

        unset($facts['role'], $facts['current_season']);

        return $facts === [];
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') ?: '0';
    }

    /**
     * Section 20: enough to debug a bad answer, never enough to leak one.
     *
     * The user's question is reduced to its length, the retrieved product ids
     * and the outcome. No message body, no key, no token, no OTP, no password.
     *
     * @param  array<string, mixed>  $contextData
     * @param  array<int, string>  $violations
     */
    private function log(?User $user, string $intent, array $contextData, string $source, array $violations): void
    {
        Log::info('aquilala.ai.assistant', [
            'user_id'          => $user?->id,
            'role'             => $user?->role,
            'intent'           => $intent,
            'product_ids'      => $contextData['product_ids'],
            'rule_ids'         => $contextData['recommendation']['formula_ids'] ?? [],
            'missing'          => $contextData['missing'],
            'response_type'    => $this->responseType($intent, $contextData),
            'model'            => $this->llm->available() ? $this->llm->modelName() : null,
            'source'           => $source,
            'valid'            => $violations === [],
            'violations'       => $violations,
        ]);
    }
}
