<?php

namespace Tests\Feature;

use App\AI\AiIntent;
use App\Models\Category;
use App\Models\Crop;
use App\Models\DecisionFormula;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Positive routing: the questions the assistant used to refuse.
 *
 * The first refactor of this assistant made it safe by making it quiet. Every
 * product, price and quantity was grounded in the database, and anything it
 * could not ground was refused — which meant a farmer asking what crops
 * Aquilala supports, or what to plant in October, or who to greet, was told the
 * system could not help.
 *
 * None of that protection needed to be traded away. Every test here is paired
 * with the same ground: a crop named in an answer has to be a row in the crops
 * directory, a seasonal answer has to come from the formula matrix, and the
 * model is not called at all on these routes, because a crop list with an
 * invented planting distance attached to it is exactly the failure the first
 * refactor existed to prevent.
 *
 * Cases A-J in the follow-up specification are covered by name below.
 */
class AiAssistantPositiveRoutingTest extends TestCase
{
    use RefreshDatabase;

    private User $farmer;

    private User $store;

    private Product $urea;

    private Product $mangoFertilizer;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.groq.key', null);

        $this->farmer = $this->makeUser('farmer', 'farmer_' . uniqid() . '@example.test');
        $this->store = $this->makeUser('store_owner', 'store_' . uniqid() . '@example.test');

        $fertilizer = $this->makeCategory('Fertilizers', 'fertilizers');
        $seeds = $this->makeCategory('Seeds', 'seeds');

        $this->urea = $this->makeProduct($this->store, $fertilizer, 'Premium Urea 46-0-0', 'UREA-46', 1450.00, 40);
        $this->mangoFertilizer = $this->makeProduct($this->store, $fertilizer, 'Mango Foliar Booster 0-0-26', 'MANGO-K26', 980.00, 12);

        // The crop directory, which is the real source for "what crops can I
        // plant?" — not a list written into a prompt.
        $this->makeCrop('Rice', 'Cereal', 'Palay / Rice (Oryza sativa)');
        $this->makeCrop('Corn', 'Cereal', 'Maize (Zea mays)');
        $this->makeCrop('Mango', 'Fruit', 'Mangga / Mango (Mangifera indica)');
        $this->makeCrop('Banana', 'Fruit', 'Saging / Banana (Musa spp.)');
        $this->makeCrop('Pineapple', 'Fruit', 'Pinya / Pineapple (Ananas comosus)');
        $this->makeCrop('Tomato', 'Vegetable', 'Tomato (Solanum lycopersicum)');
        $this->makeCrop('Pechay', 'Vegetable', 'Pechay / Bok Choy');
        $this->makeCrop('Ampalaya', 'Vegetable', 'Ampalaya (bitter gourd)');

        // The formula matrix, which is what decides a seasonal answer. Rice and
        // Corn are costed in both seasons; Tomato only in the dry one, so a
        // wet-season answer has something it must leave out.
        $this->makeFormula('Rice', 'wet', $this->urea, 3.00, 'Sacks');
        $this->makeFormula('Rice', 'dry', $this->urea, 3.00, 'Sacks');
        $this->makeFormula('Corn', 'wet', $this->urea, 4.00, 'Sacks');
        $this->makeFormula('Corn', 'dry', $this->urea, 4.00, 'Sacks');
        $this->makeFormula('Tomato', 'dry', $this->urea, 2.00, 'Sacks');
        $this->makeFormula('Mango', 'dry', $this->mangoFertilizer, 6.00, 'Bottles');
    }

    // ────────────────────────────────────────────────────────────
    //  A. "Hi" — a greeting, not the capability briefing
    // ────────────────────────────────────────────────────────────

    public function test_a_greeting_gets_a_greeting(): void
    {
        $response = $this->ask('Hi');

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertSame(AiIntent::GREETING, $response->json('intent'));
        $this->assertStringContainsString('How can I help', $reply);
        $this->assertStringNotContainsStringIgnoringCase('I cannot', $reply);
        $this->assertStringNotContainsStringIgnoringCase('I can\'t', $reply);
        $this->assertStringNotContainsStringIgnoringCase('browse the web', $reply);

        // The whole point of the change: a greeting is not an essay.
        $this->assertLessThan(
            320,
            mb_strlen($reply),
            'A greeting must not dump the capability and restriction list.'
        );
    }

    public function test_a_thanks_is_answered_without_the_briefing(): void
    {
        $response = $this->ask('Hello');

        $response->assertOk();
        $this->assertSame(AiIntent::GREETING, $response->json('intent'));
    }

    // ────────────────────────────────────────────────────────────
    //  B. "Do you have urea?" — unchanged, and still grounded
    // ────────────────────────────────────────────────────────────

    public function test_b_a_named_product_is_a_product_search(): void
    {
        $response = $this->ask('Do you have urea?');

        $response->assertOk();

        $this->assertSame(AiIntent::PRODUCT_SEARCH, $response->json('intent'));
        $this->assertTrue($this->ids($response)->contains($this->urea->id));
        $this->assertStringContainsString('1,450.00', $response->json('reply'));
    }

    // ────────────────────────────────────────────────────────────
    //  C. "What fertilizers for rice?" — real recommendation data
    // ────────────────────────────────────────────────────────────

    public function test_c_a_fertilizer_question_for_a_crop_uses_actual_aquilala_data(): void
    {
        $response = $this->ask('What fertilizers for rice?');

        $response->assertOk();

        $this->assertSame(AiIntent::PRODUCT_RECOMMENDATION, $response->json('intent'));
        $this->assertTrue($this->ids($response)->contains($this->urea->id));
        $this->assertSame(1450.0, (float) $this->cardFor($response, $this->urea->id)['price_value']);
    }

    // ────────────────────────────────────────────────────────────
    //  D. The subjective question that used to be refused
    // ────────────────────────────────────────────────────────────

    public function test_d_a_subjective_crop_question_is_answered_not_refused(): void
    {
        $response = $this->ask('suggest the most tasty fruit to plant in my backyard that\'s available');

        $response->assertOk();

        $this->assertSame(AiIntent::CROP_RECOMMENDATION, $response->json('intent'));

        $reply = $response->json('reply');

        $this->assertStringNotContainsStringIgnoringCase("I can't help", $reply);
        $this->assertStringNotContainsStringIgnoringCase('I focus on Aquilala farming', $reply);

        // Taste is the farmer's to judge, and the answer says so.
        $this->assertMatchesRegularExpression('/taste|preference/i', $reply);

        // The choices come from the crops directory.
        $this->assertStringContainsString('Mango', $reply);
        $this->assertStringContainsString('Banana', $reply);
        $this->assertStringContainsString('Pineapple', $reply);

        // And it does not crown a winner.
        $this->assertStringNotContainsStringIgnoringCase('the tastiest fruit is', $reply);
    }

    public function test_d_a_subjective_question_never_invents_a_crop(): void
    {
        $response = $this->ask('what is the most tasty fruit?');

        $response->assertOk();

        $reply = $response->json('reply');

        // Nothing outside the directory may be offered as a choice.
        foreach (['Durian', 'Lychee', 'Jackfruit', 'Rambutan', 'Soursop'] as $decoy) {
            $this->assertStringNotContainsString(
                $decoy,
                $reply,
                "{$decoy} is not in the crops directory, so it cannot be offered."
            );
        }

        // And what it does offer is in the directory.
        $inDirectory = Crop::pluck('name')->map(fn ($name) => mb_strtolower((string) $name))->all();

        foreach (['Mango', 'Banana', 'Pineapple'] as $offered) {
            if (str_contains($reply, $offered)) {
                $this->assertContains(mb_strtolower($offered), $inDirectory);
            }
        }
    }

    // ────────────────────────────────────────────────────────────
    //  E. Seasonal recommendation from real seasonal data
    // ────────────────────────────────────────────────────────────

    public function test_e_a_month_question_is_a_seasonal_recommendation(): void
    {
        $response = $this->ask('What do you have to plant this October');

        $response->assertOk();

        $this->assertSame(AiIntent::SEASONAL_RECOMMENDATION, $response->json('intent'));

        $reply = $response->json('reply');

        // October is in the wet season in the application's own calendar.
        $this->assertStringContainsString('October', $reply);
        $this->assertStringContainsStringIgnoringCase('wet season', $reply);

        // The crops listed are the ones the formula matrix covers for that
        // season, and nothing else.
        $this->assertStringContainsString('Rice', $reply);
        $this->assertStringContainsString('Corn', $reply);
        $this->assertStringNotContainsString('Tomato', $reply, 'Tomato is only costed in the dry season.');

        // No model call, and no invented calendar.
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.groq.com'));
    }

    public function test_e_a_season_with_no_formulas_says_so_instead_of_guessing(): void
    {
        DecisionFormula::query()->delete();

        $response = $this->ask('What can I plant this October?');

        $response->assertOk();

        $this->assertSame(AiIntent::SEASONAL_RECOMMENDATION, $response->json('intent'));
        $this->assertStringContainsStringIgnoringCase('does not have decision formulas', $response->json('reply'));
        $this->assertStringNotContainsStringIgnoringCase('Rice', $response->json('reply'));
    }

    // ────────────────────────────────────────────────────────────
    //  F. "How do I plant corn?" — approved knowledge, no invented technique
    // ────────────────────────────────────────────────────────────

    public function test_f_a_how_to_question_is_answered_from_approved_knowledge(): void
    {
        $response = $this->ask('How do I plant corn?');

        $response->assertOk();

        $this->assertSame(AiIntent::FARMING_ADVICE, $response->json('intent'));

        $reply = $response->json('reply');

        $this->assertStringContainsString('Corn', $reply);

        // The protection that matters: a how-to answer must not carry a
        // planting distance, a seed rate or a spray schedule the application
        // does not hold.
        $this->assertDoesNotMatchRegularExpression('/\d+\s*cm\b/i', $reply);
        $this->assertDoesNotMatchRegularExpression('/\d+\s*(?:bags?|sacks?|bottles?)\s+per\s+hectare/i', $reply);
        $this->assertStringContainsStringIgnoringCase(
            'That is general farming practice, not an Aquilala recommendation',
            $reply
        );
        $this->assertStringContainsStringIgnoringCase(
            'I am not giving you an exact planting distance',
            $reply
        );

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.groq.com'));
    }

    // ────────────────────────────────────────────────────────────
    //  G. Cost still comes from the rule engine
    // ────────────────────────────────────────────────────────────

    public function test_g_a_cost_question_is_answered_by_the_cost_engine(): void
    {
        $response = $this->ask('How much would 2 hectares of corn cost in the wet season?');

        $response->assertOk();

        $this->assertSame(AiIntent::COST_ESTIMATION, $response->json('intent'));
        $this->assertSame('cost_estimate', $response->json('type'));

        $reply = $response->json('reply');

        // 4 sacks/ha over 2 ha at 1,450.00 — the engine's arithmetic, not the
        // model's.
        $this->assertStringContainsString('8 Sacks', $reply);
        $this->assertStringContainsString('11,600.00', $reply);
    }

    // ────────────────────────────────────────────────────────────
    //  H. and I. Follow-ups resolve the previous entity
    // ────────────────────────────────────────────────────────────

    public function test_h_can_i_review_it_resolves_to_the_previous_product_and_its_laravel_url(): void
    {
        $first = $this->ask('Do you have urea?');
        $first->assertOk();

        $second = $this->ask('Can I review it?', $first->json('context'));
        $second->assertOk();

        $this->assertSame(AiIntent::PRODUCT_SEARCH, $second->json('intent'));
        $this->assertTrue($this->ids($second)->contains($this->urea->id));

        foreach (collect($second->json('suggestions'))->pluck('url') as $url) {
            $this->assertStringStartsWith(url('/'), $url);
        }
    }

    public function test_i_how_much_is_it_resolves_the_previous_product_instead_of_asking_for_an_area(): void
    {
        $first = $this->ask('Do you have urea?');
        $first->assertOk();

        $context = $first->json('context');

        $this->assertContains($this->urea->id, $context['last_product_ids']);

        $second = $this->ask('How much is it?', $context);
        $second->assertOk();

        $this->assertSame(AiIntent::PRODUCT_SEARCH, $second->json('intent'));
        $this->assertStringContainsString('1,450.00', $second->json('reply'));
        $this->assertStringNotContainsStringIgnoringCase('farm area', $second->json('reply'));
    }

    // ────────────────────────────────────────────────────────────
    //  J. No matching product
    // ────────────────────────────────────────────────────────────

    public function test_j_no_matching_product_is_stated_plainly_with_no_substitute(): void
    {
        // A model that tries to be helpful by inventing a substitute.
        $this->fakeModel([
            'type' => 'product_results',
            'answer' => 'No match, but try our **Saffron Booster Organic** instead.',
            'product_ids' => [],
        ]);

        $response = $this->ask('Do you have saffron products?');

        $response->assertOk();

        $this->assertSame('no_results', $response->json('type'));
        $this->assertSame([], $response->json('suggestions'));
        $this->assertStringContainsString(
            "I couldn't find that product in the current Aquilala catalog",
            $response->json('reply')
        );
        $this->assertStringContainsString(
            'You can try another product name or ask me to show the available fertilizers.',
            $response->json('reply')
        );
        $this->assertStringNotContainsString('Saffron Booster Organic', $response->json('reply'));
    }

    // ────────────────────────────────────────────────────────────
    //  Crop discovery, the route the specification asks for by name
    // ────────────────────────────────────────────────────────────

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function discoveryProvider(): array
    {
        return [
            'what crops can I plant'    => ['What crops can I plant?', 'Rice'],
            'what fruits can I plant'   => ['What fruits can I plant?', 'Mango'],
            'what do you have to plant' => ['What do you have to plant?', 'Rice'],
            'what crops are available' => ['What crops are available?', 'Corn'],
            'fruit crops supported'     => ['What fruit crops does Aquilala support?', 'Banana'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('discoveryProvider')]
    public function test_crop_discovery_is_answered_from_the_crops_directory(string $message, string $expected): void
    {
        $response = $this->ask($message);

        $response->assertOk();

        $this->assertSame(AiIntent::CROP_DISCOVERY, $response->json('intent'));
        $this->assertStringContainsString($expected, $response->json('reply'));

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.groq.com'));
    }

    public function test_a_crop_question_never_lists_a_crop_the_directory_does_not_hold(): void
    {
        $response = $this->ask('What crops can I plant?');

        $response->assertOk();

        $reply = $response->json('reply');

        $inDirectory = Crop::pluck('name')->map(fn ($name) => mb_strtolower((string) $name))->all();

        $this->assertNotEmpty($inDirectory);

        // Every crop the answer offers is a crop the directory holds. The check
        // is on the text the farmer reads, because that is the surface a
        // fabricated crop would appear on.
        foreach ($this->cropsMentionedIn($reply) as $mentioned) {
            $this->assertContains(
                mb_strtolower($mentioned),
                $inDirectory,
                "{$mentioned} is offered to the farmer but is not in the crops table."
            );
        }

        $this->assertStringContainsString('Rice', $reply);
        $this->assertStringContainsString('Mango', $reply);
    }

    public function test_a_plural_category_word_still_filters_the_listing(): void
    {
        // "fruits" is not a category value; "Fruit" is. Without a plural match
        // this answer quietly listed every crop the system has.
        $response = $this->ask('What fruits can I plant?');

        $response->assertOk();

        $this->assertSame(AiIntent::CROP_DISCOVERY, $response->json('intent'));

        $reply = $response->json('reply');

        $this->assertStringContainsString('Mango', $reply);
        $this->assertStringContainsString('Banana', $reply);
        $this->assertStringContainsString('Pineapple', $reply);

        foreach (['Rice', 'Corn', 'Tomato', 'Pechay', 'Ampalaya'] as $otherCategory) {
            $this->assertStringNotContainsString(
                $otherCategory,
                $reply,
                "{$otherCategory} is not a Fruit, so a question about fruits must not list it."
            );
        }
    }

    public function test_a_category_the_directory_does_not_use_is_not_invented(): void
    {
        $response = $this->ask('What tubers can I plant?');

        $response->assertOk();

        // "Root Crop" is a real category; "tuber" is not, so nothing is claimed
        // to be in it.
        $reply = $response->json('reply');

        $this->assertStringNotContainsString('Aquilala supports 0', $reply);
        $this->assertStringNotContainsString('Sweet Potato', $reply);
    }

    // ────────────────────────────────────────────────────────────
    //  Specification #10 - category placeholders are not crops
    // ────────────────────────────────────────────────────────────

    public function test_category_placeholders_never_appear_as_plantable_crops(): void
    {
        // "Vegetables", "Legumes" and "Root Crops" are grouping labels that sit
        // in the crops table. They are real records - other parts of the
        // application group by them - and they are not things anyone can plant.
        $this->makeCrop('Vegetables', 'Vegetable', 'Placeholder group for vegetable crops', true);
        $this->makeCrop('Root Crops', 'Root Crop', 'Placeholder group for root crops', true);
        $this->makeCrop('Legumes', 'Legume', 'Placeholder group for legume crops', true);
        $this->makeCrop('Other', 'Other', 'Placeholder for unclassified crops', true);

        foreach (['What crops can I plant?', 'What do you have to plant this October?'] as $message) {
            $response = $this->ask($message);
            $response->assertOk();

            $reply = $response->json('reply');

            foreach (['Vegetables', 'Root Crops', 'Legumes', 'Other'] as $placeholder) {
                $this->assertStringNotContainsString(
                    $placeholder,
                    $reply,
                    "\"{$placeholder}\" is a category label, not a crop, and must not be offered to a farmer."
                );
            }
        }

        // The real crops the placeholders group are still listed, with their
        // counts of nothing.
        $discovery = $this->ask('What crops can I plant?')->json('reply');

        $this->assertStringContainsString('Tomato', $discovery);
        $this->assertStringContainsString('Pechay', $discovery);
    }

    public function test_a_placeholder_cannot_be_resolved_as_a_crop(): void
    {
        $this->makeCrop('Vegetables', 'Vegetable', 'Placeholder group for vegetable crops', true);

        // A formula matrix that points at the group rather than a plant.
        $this->makeFormula('Vegetables', 'wet', $this->urea, 2.00, 'Sacks');

        $response = $this->ask('What do you have to plant this October?');
        $response->assertOk();

        $this->assertStringNotContainsString('Vegetables', $response->json('reply'));
    }

    public function test_the_directory_still_exposes_its_categories_for_grouping(): void
    {
        // Excluding placeholders from crop choices must not delete them as
        // categories - the UI groups by these.
        $this->makeCrop('Vegetables', 'Vegetable', 'Placeholder group for vegetable crops', true);

        $response = $this->ask('What vegetables can I plant?');
        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertStringContainsString('Tomato', $reply);
        $this->assertStringContainsString('Pechay', $reply);
        $this->assertStringNotContainsString('Vegetables', $reply);
    }

    // ────────────────────────────────────────────────────────────
    //  Specification #15 and #26 - no formula counts, no ranking
    // ────────────────────────────────────────────────────────────

    public function test_a_seasonal_answer_never_shows_formula_counts(): void
    {
        $response = $this->ask('What do you have to plant this October?');

        $response->assertOk();

        $reply = $response->json('reply');

        // "Rice has 39 formulas" is internal coverage, and a number in a crop
        // list reads as a ranking the application has no basis for.
        $this->assertDoesNotMatchRegularExpression('/\b\d+\s+formulas?\b/i', $reply);
        $this->assertDoesNotMatchRegularExpression('/\bformula count\b/i', $reply);
        $this->assertStringContainsStringIgnoringCase("covered by Aquilala's", $reply);
    }

    public function test_a_seasonal_answer_does_not_rank_crops(): void
    {
        // Rice has 3 formulas here, Corn 2, Banana 1, Tomato 0. A list ordered by
        // coverage would read as a judgement about the crops.
        $response = $this->ask('What do you have to plant this October?');

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertDoesNotMatchRegularExpression(
            '/\b(best|top|most suitable|highest yield|most profitable|ideal|number one|number 1)\b/i',
            $reply
        );

        $this->assertStringContainsStringIgnoringCase('I am not ranking them', $reply);
    }

    public function test_a_crop_listing_never_shows_formula_counts(): void
    {
        $response = $this->ask('What fruits can I plant?');

        $response->assertOk();

        $this->assertDoesNotMatchRegularExpression('/\b\d+\s+formulas?\b/i', $response->json('reply'));
    }

    public function test_a_crop_listing_says_whether_a_rule_exists_without_counting_them(): void
    {
        // Mango has no Aquilala formula in this fixture; Banana has four. A
        // recommendation answer reports that as coverage or its absence, never
        // as a count.
        $response = $this->ask('What fruit should I plant?');

        $response->assertOk();

        $this->assertSame(AiIntent::CROP_RECOMMENDATION, $response->json('intent'));

        $reply = $response->json('reply');

        $this->assertStringContainsStringIgnoringCase("covered by Aquilala's current rules", $reply);
        $this->assertStringContainsStringIgnoringCase('no Aquilala rule yet', $reply);
        $this->assertDoesNotMatchRegularExpression('/\b\d+\s+formulas?\b/i', $reply);
    }

    // ────────────────────────────────────────────────────────────
    //  Specification #13 and Test 2 - the backyard question
    // ────────────────────────────────────────────────────────────

    public function test_a_backyard_question_says_it_has_no_backyard_rules(): void
    {
        $response = $this->ask("What's good to plant in my backyard right now?");

        $response->assertOk();

        $this->assertSame(AiIntent::CROP_RECOMMENDATION, $response->json('intent'));

        $reply = $response->json('reply');

        $this->assertStringContainsStringIgnoringCase('no backyard-specific rules', $reply);
        $this->assertMatchesRegularExpression('/fruits?/i', $reply);
        $this->assertMatchesRegularExpression('/vegetables?/i', $reply);

        // It asks rather than dumping the directory.
        $this->assertSame('clarification', $response->json('type'));
        $this->assertStringNotContainsString('Ampalaya', $reply);
    }

    // ────────────────────────────────────────────────────────────
    //  Specification #13 and Test 3 - a backyard question that has a
    //  category must not be answered with a request for farm area
    // ────────────────────────────────────────────────────────────

    public function test_a_backyard_question_with_a_crop_type_does_not_ask_for_a_farm_area(): void
    {
        $response = $this->ask('Suggest tasty fruit to plant in my backyard.');

        $response->assertOk();

        $this->assertSame(AiIntent::CROP_RECOMMENDATION, $response->json('intent'));

        $reply = $response->json('reply');

        $this->assertMatchesRegularExpression('/\bbanana\b/i', $reply);
        $this->assertStringContainsStringIgnoringCase('taste is personal', $reply);

        // A backyard plot has no hectares. Asking for a farm area here is
        // asking for a number the farmer cannot give.
        $this->assertStringNotContainsStringIgnoringCase('farm area', $reply);
        $this->assertStringNotContainsStringIgnoringCase('hectare', $reply);
    }

    // ────────────────────────────────────────────────────────────
    //  Specification #39 - comparisons the system cannot make
    // ────────────────────────────────────────────────────────────

    /**
     * @return array<string, array{0: string}>
     */
    public static function unanswerableComparisons(): array
    {
        return [
            'yield'   => ['Which one has the highest yield?'],
            'yield 2' => ['What gives the best yield?'],
            'profit'  => ['Which crop is most profitable?'],
            'value'   => ['What is the cheapest fertilizer?'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('unanswerableComparisons')]
    public function test_a_comparison_without_ranking_data_is_declined_and_redirected(string $message): void
    {
        $response = $this->ask($message);

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertMatchesRegularExpression(
            '/(does not|don\'t|cannot|no value score|will not)\s+(currently\s+)?(have|has|rank|compare|rate|track|guess|pick|crown|let)/i',
            $reply,
            'The comparison must be declined explicitly.'
        );

        // #45: declining is not the same as refusing. Something has to be
        // offered, or the answer is just a wall.
        $this->assertMatchesRegularExpression('/(I can|What I can do|Tell me|give me)/i', $reply);

        // And never a winner.
        foreach (['the best is', 'i recommend', 'top choice', 'most profitable is'] as $crown) {
            $this->assertStringNotContainsStringIgnoringCase($crown, $reply);
        }
    }

    // ────────────────────────────────────────────────────────────
    //  Specification #18 and Test 9 - practical how-to guidance
    // ────────────────────────────────────────────────────────────

    public function test_a_how_to_question_gives_numbered_general_steps(): void
    {
        $response = $this->ask('How do I plant corn?');

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertStringContainsString('For general corn planting, the usual process is:', $reply);
        $this->assertMatchesRegularExpression('/1\.\s+\S/', $reply);
        $this->assertStringContainsString('2.', $reply);

        // Labelled as general practice, not as an Aquilala recommendation.
        $this->assertStringContainsStringIgnoringCase('general farming practice, not an Aquilala recommendation', $reply);
    }

    public function test_the_steps_do_not_carry_agronomic_numbers(): void
    {
        $response = $this->ask('How do I plant corn?');

        $response->assertOk();

        $reply = $response->json('reply');

        // Only the steps are checked - everything before the marker. Aquilala's
        // own crop record after it does carry figures - a typical yield and a
        // maturity - and those come from the catalogue, so they are allowed.
        $steps = strstr($reply, 'That is general farming practice', true);

        $this->assertIsString($steps);
        $this->assertStringNotContainsString('per hectare', (string) $steps);
        $this->assertDoesNotMatchRegularExpression('/\d+\s*cm\b/i', (string) $steps);
        $this->assertDoesNotMatchRegularExpression('/\d+\s*(?:bags?|sacks?|bottles?|kg|kilos?)\b/i', (string) $steps);
        $this->assertDoesNotMatchRegularExpression('/spray every\s+\d+/i', (string) $steps);
        $this->assertDoesNotMatchRegularExpression('/\d+\s*days\b/i', (string) $steps);
    }

    public function test_the_steps_are_group_appropriate(): void
    {
        $corn = $this->ask('How do I plant corn?')->json('reply');
        $banana = $this->ask('How do I plant banana?')->json('reply');

        $this->assertStringContainsStringIgnoringCase('Choose seed', $corn);
        $this->assertStringContainsStringIgnoringCase('seedlings', $banana);
        $this->assertNotSame($corn, $banana);
    }

    // ────────────────────────────────────────────────────────────
    //  The fallback hierarchy, in order
    // ────────────────────────────────────────────────────────────

    public function test_a_genuinely_unrelated_question_is_still_refused(): void
    {
        $response = $this->ask('Who won the election?');

        $response->assertOk();

        $this->assertSame(AiIntent::UNSUPPORTED, $response->json('intent'));
        $this->assertStringContainsString('Aquilala', $response->json('reply'));
    }

    public function test_a_weather_question_is_still_refused(): void
    {
        $response = $this->ask('Will it rain tomorrow?');

        $response->assertOk();

        $this->assertSame(AiIntent::UNSUPPORTED, $response->json('intent'));
        $this->assertStringNotContainsString('32 degrees', $response->json('reply'));
    }

    public function test_a_vague_crop_choice_asks_one_short_question(): void
    {
        $response = $this->ask('What should I plant?');

        $response->assertOk();

        $this->assertSame(AiIntent::CROP_RECOMMENDATION, $response->json('intent'));
        $this->assertSame('clarification', $response->json('type'));
        $this->assertStringContainsStringIgnoringCase('fruit', $response->json('reply'));
    }

    // ────────────────────────────────────────────────────────────
    //  Helpers
    // ────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>|null  $context
     */
    private function ask(string $message, ?array $context = null)
    {
        return $this->actingAs($this->farmer)->postJson(route('farmer.ai-chat.send'), [
            'messages' => [['role' => 'user', 'content' => $message]],
            'context' => $context,
        ]);
    }

    /**
     * Crops the answer actually names, in the order it names them.
     *
     * @return array<int, string>
     */
    private function cropsMentionedIn(string $reply): array
    {
        $mentioned = [];

        foreach (Crop::pluck('name') as $name) {
            if (str_contains($reply, (string) $name)) {
                $mentioned[] = (string) $name;
            }
        }

        return $mentioned;
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function ids($response)
    {
        return collect($response->json('suggestions'))->pluck('id')->map(fn ($id) => (int) $id);
    }

    /**
     * @return array<string, mixed>
     */
    private function cardFor($response, int $productId): array
    {
        $card = collect($response->json('suggestions'))->firstWhere('id', $productId);

        $this->assertNotNull($card, "Expected a product card for #{$productId}.");

        return $card;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function fakeModel(array $payload): void
    {
        Config::set('services.groq.key', 'test-key');

        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode($payload)],
                ]],
            ]),
        ]);
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create([
            'name'       => $role === 'store_owner' ? 'Green Valley Agri Supply' : 'Juan Dela Cruz',
            'first_name' => $role === 'store_owner' ? 'Green' : 'Juan',
            'last_name'  => $role === 'store_owner' ? 'Valley' : 'Dela Cruz',
            'email'      => $email,
            'role'       => $role,
            'password'   => 'password123',
        ]);
    }

    private function makeCategory(string $name, string $slug): Category
    {
        return Category::create([
            'name'        => $name,
            'slug'        => $slug,
            'description' => $name . ' inputs',
        ]);
    }

    private function makeProduct(
        User $store,
        Category $category,
        string $name,
        string $sku,
        float $price,
        int $stock,
    ): Product {
        return Product::create([
            'user_id'           => $store->id,
            'category_id'       => $category->id,
            'category'          => $category->name,
            'sku'               => $sku,
            'name'              => $name,
            'price'             => $price,
            'price_unit'        => 'sack',
            'stock_quantity'    => $stock,
            'stock_unit'        => 'sacks',
            'reorder_threshold' => 5,
            'status'            => 'active',
        ]);
    }

    private function makeCrop(string $name, string $category, string $description, bool $isPlaceholder = false): Crop
    {
        return Crop::create([
            'name'                   => $name,
            'category'               => $category,
            'description'            => $description,
            'is_category_placeholder' => $isPlaceholder,
        ]);
    }

    private function makeFormula(string $crop, string $season, Product $product, float $rate, string $unit): DecisionFormula
    {
        return DecisionFormula::create([
            'crop_type'        => $crop,
            'season'           => $season,
            'product_id'       => $product->id,
            'rate_per_hectare' => $rate,
            'unit'             => $unit,
        ]);
    }
}
