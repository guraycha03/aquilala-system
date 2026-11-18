<?php

namespace App\AI;

use App\AI\Prompts\AquilalaAssistantPrompt;
use App\Models\User;
use App\Models\AssociationEvent;
use App\Rules\CropRecommendationEngine;
use App\Services\FarmingKnowledgeService;
use App\Services\WeatherService;
use App\Models\HarvestReport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
    /**
     * The words a store owner uses when they ask about their own demand:
     * what is trending, what to restock, what the quotations say.
     *
     * @var array<int, string>
     */
    private const STORE_DEMAND_PATTERNS = [
        '/\btrending\b/',
        '/\bin\s+demand\b/',
        '/\bdemand\s+trends?\b/',
        '/\bmost\s+(?:quoted|demanded|asked\s+for|requested|sell(?:ing)?)\b/',
        '/\bwhat\s+are\s+farmers\s+(?:asking|quoting|requesting)\b/',
        '/\bre-?stock(?:ing)?\b/',
        '/\bwhat\s+should\s+i\s+(?:stock|order|carry|keep)\b/',
        '/\bquotation\s+trends?\b/',
        '/\bmy\s+(?:quotation|quote|quotations|quotes)\s+trends?\b/',
        '/\bwhat\s+(?:is|are)\s+(?:hot|selling)\b/',
        '/\b(?:which|what)\s+products?\s+(?:sell|sold)\s+most\b/',
    ];

    /**
     * The words that mark an answer about the assistant rather than about the
     * subject: capabilities, instructions, context, access. An answer that
     * talks about itself is not an answer to the question.
     *
     * @var array<int, string>
     */
    private const META_ANSWER_MARKERS = [
        'my capabilities',
        'my capabilities are',
        'i cannot',
        'i can only',
        "i'm unable",
        'i am unable',
        'as an ai',
        "i'm an ai",
        'my instructions',
        'the context provided',
        'the verified context',
        'the aquilala context',
        'the data you gave me',
        'i do not have access',
        "i don't have access",
        'i am grounded',
        'i am verified',
        'based on the provided context',
        'according to my',
    ];

    public function __construct(
        private readonly AiIntentRouter $router,
        private readonly AiContextBuilder $contextBuilder,
        private readonly AiResponseValidator $validator,
        private readonly AiResponseValidatorAi $validatorAi,
        private readonly AiLlmClient $llm,
        private readonly FarmingKnowledgeService $knowledge,
        private readonly \App\Services\ProductSearchService $catalog,
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
        if ($user !== null) {
            $calendarResult = $this->handleCalendarAction($message, $user);

            if ($calendarResult !== null) {
                return $calendarResult;
            }
        }

        $context = AiConversationContext::fromArray($conversationContext);

        // ── Greetings first ─────────────────────────────────────────────
        //
        // A courtesy always gets a courtesy back, before any database lookup
        // or intent routing. When the message opens with a greeting and
        // carries a real question after it, the greeting is stripped and
        // prepended to the answer so the user feels heard and still gets
        // an answer to what they actually asked.
        $greetingPrefix = $this->extractGreetingPrefix($message);
        $greetingHandled = false;
        if ($greetingPrefix !== null) {
            $stripped = $this->stripLeadingGreeting($message);

            // Nothing but a courtesy left: answer the courtesy. This also
            // covers a second greeting behind the first ("Hi, good evening!")
            // so the user gets one greeting, not two stacked.
            if (trim($stripped) === '' || $this->assistantIsGreetingOnly($stripped)) {
                return [
                    'reply'       => $greetingPrefix,
                    'type'        => 'answer',
                    'suggestions' => [],
                    'context'     => $context->toArray(),
                    'intent'      => AiIntent::GREETING,
                    'source'      => 'conversation',
                    'violations'  => [],
                ];
            }

            if ($this->assistantIsSystemHelp($stripped)) {
                return [
                    'reply'       => $greetingPrefix . "\n\n" . $this->assistantSystemHelpReply($user?->role ?? '', $stripped),
                    'type'        => 'answer',
                    'suggestions' => [],
                    'context'     => $context->toArray(),
                    'intent'      => AiIntent::GREETING,
                    'source'      => 'conversation',
                    'violations'  => [],
                ];
            }

            // Remember the greeting; it is prepended to the final reply below.
            $context = $context->withGreeting($greetingPrefix);
            // The rest of this turn is about what came after the greeting,
            // so every later check sees the question, not the courtesy.
            $message = $stripped;
            $greetingHandled = true;
        }

        // How to do something in the account comes first: this page exists to
        // guide users through tasks, and "paano maglagay ng product" needs
        // steps, not a link list or a stock summary.
        if (($howTo = $this->howToAnswer($message, (string) ($user?->role ?? ''), $context)) !== null) {
            return $howTo;
        }

        // Stock and inventory questions in any phrasing a store owner uses.
        // The answer is read with InventoryController's own predicates, so
        // the chat and the inventory page can never disagree about what is
        // low or what is out of stock.
        if ($user?->role === 'store_owner' && $this->isStockQuestion($message)) {
            return $this->storeStockAnswer($user, $context, $message);
        }

        if ($user?->role === 'store_owner'
            && preg_match('/\b(not a farmer|not farmer|store owner|store-owner)\b/i', $message) === 1) {
            return [
                'reply'       => 'Noted — I will treat you as a store owner from here. Ask me about your product listings, inventory, quotation demand trends, top products, or prices, and I will help with those.',
                'type'        => 'answer',
                'suggestions' => [],
                'context'     => $context->toArray(),
                'intent'      => AiIntent::SYSTEM_HELP,
                'source'      => 'conversation',
                'violations'  => [],
            ];
        }

        if (preg_match('/^(?:ok(?:ay)?|alright|got\s+it|sure|fine|great|cool|thanks|thank\s+you|yes|no)[!?.\s]*$/iu', trim($message)) === 1) {
            if (($user?->role ?? null) === 'store_owner') {
                $reply = preg_match('/^(?:thanks|thank\s+you)$/iu', trim($message)) === 1
                    ? 'You are welcome. I am here to help with your store and Aquilala products.'
                    : 'Understood. I will keep that in mind — ask me anything about your store, products, inventory, trends, or prices whenever you are ready.';
            } else {
                $reply = preg_match('/^(?:thanks|thank\s+you)$/iu', trim($message)) === 1
                    ? 'You are welcome. I am here whenever you need help with Aquilala farming.'
                    : 'Understood. I will keep that in mind — ask me anything about your crop, product, or cost estimate whenever you are ready.';
            }

            return [
                'reply'       => $reply,
                'type'        => 'answer',
                'suggestions' => [],
                'context'     => $context->toArray(),
                'intent'      => AiIntent::GREETING,
                'source'      => 'conversation',
                'violations'  => [],
            ];
        }

        $isStoreDemandQuestion = $user?->role === 'store_owner'
            && preg_match('/\b(sell|sold|restock|demand|top products?|trend|most)\b/i', $message) === 1;

        if (! $isStoreDemandQuestion
            && ($pageReply = $this->pageLinkReply($message, (string) ($user?->role ?? ''))) !== null) {
            return [
                'reply'       => $pageReply['reply'],
                'type'        => 'answer',
                'suggestions' => [],
                'context'     => $context->toArray(),
                'intent'      => AiIntent::SYSTEM_HELP,
                'source'      => 'conversation',
                'violations'  => [],
            ];
        }

        if (preg_match('/\bweather\b/iu', $message) === 1
            && preg_match('/\b(tokyo|manila|cebu|davao|makati|quezon|baguio)\b/iu', $message) !== 1) {
            return [
                'reply'       => $this->weatherSummary($message),
                'type'        => 'answer',
                'suggestions' => [],
                'context'     => $context->toArray(),
                'intent'      => AiIntent::GENERAL_FARMING,
                'source'      => 'conversation',
                'violations'  => [],
            ];
        }

        if (preg_match('/\bharvest\b/iu', $message) === 1
            && preg_match('/\b(overview|performance|summary|activity|report|this\s+(?:year|month))\b/iu', $message) === 1) {
            return $this->harvestOverviewAnswer($message, $user);
        }

        // An administrator asking about what farmers have been doing today or
        // recently gets association-wide activity from the audit log — not
        // their own (likely empty) harvest reports, and not a refusal.
        if ($user?->role === 'admin' && $this->isAdminFarmerActivityQuestion($message)) {
            return $this->adminFarmerActivityAnswer($message);
        }

        // A greeting that was already peeled off above is answered by the
        // prefix; this branch only catches a message that is nothing but a
        // greeting in a shape the extractor does not open with.
        if (! $greetingHandled && $this->assistantIsGreetingFlexible($message)) {
            return [
                'reply'       => $this->assistantGreetingReply($message, $context->toArray()['facts'] ?? []),
                'type'        => 'answer',
                'suggestions' => [],
                'context'     => $context->toArray(),
                'intent'      => AiIntent::GREETING,
                'source'      => 'conversation',
                'violations'  => [],
            ];
        }

        if ($this->assistantIsSystemHelp($message)) {
            $pages = $this->assistantPageCatalog($user?->role ?? '');
            $links = [];

            foreach ($pages as $label => $routeName) {
                try {
                    $links[] = '- [' . $label . '](' . route($routeName) . ')';
                } catch (\Throwable $e) {
                    continue;
                }

                if (count($links) >= 6) {
                    break;
                }
            }

            if ($user?->role === 'store_owner') {
                $reply = "I'm the Aquilala assistant for store owners. I can help with your product listings, inventory, quotation demand trends, top products, and prices.\n\n"
                    . "Ask me directly, for example:\n"
                    . '- "Show my store inventory"\n'
                    . '- "Which products sell most?"\n'
                    . '- "How do I add a new product?"\n'
                    . '- "Where can I find Input Formulas?"\n';
            } else {
                $reply = "I'm Aquilala's assistant for your farm. I can help with product prices, fertilizer and crop advice, cost estimates, and planting guidance.\n\n"
                    . "Ask me directly, for example:\n"
                    . '- "Do you have urea products?"\n'
                    . '- "What fertilizers for corn?"\n'
                    . '- "How much would 2 hectares of corn cost?"\n'
                    . '- "How do I plant corn?"\n'
                    . '- "Where can I find My Cost Quotations?"\n';
            }

            if ($links !== []) {
                $reply .= "\nSome pages you may need:\n" . implode("\n", $links);
            }

            if (trim($message) === '' || preg_match('/\bthis\b/iu', $message) === 1) {
                $reply .= "\n\nIf you mean a specific page or step, tell me which one and I will guide you.";
            }

            return [
                'reply'       => $reply,
                'type'        => 'answer',
                'suggestions' => [],
                'context'     => $context->toArray(),
                'intent'      => AiIntent::SYSTEM_HELP,
                'source'      => 'conversation',
                'violations'  => [],
            ];
        }

        $routing = $this->router->route($message, $context, $history);
        $intent = $routing['intent'];

        // A store owner asking about their own business is asking about the
        // store's demand, and only the store's own quotations can answer it.
        // The router reads the words; this reads who is asking.
        if ($user?->role === 'store_owner' && $this->isStoreDemandQuestion($message, $intent)) {
            $routing['intent'] = AiIntent::STORE_DEMAND;
            // Whatever the router thought was missing — a season, a farm
            // area — the store's own quotations do not need it. Leaving it
            // behind would turn a demand question into a clarification.
            $routing['missing'] = [];
            $intent = AiIntent::STORE_DEMAND;
        }

        $contextData = $this->contextBuilder->build($message, $routing, $user, $context);
        $fallback = $this->deterministicAnswer($intent, $contextData, $routing, $user, $message);

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
            || (($user?->role === 'store_owner') && in_array($intent, [AiIntent::GENERAL_FARMING, AiIntent::FARMING_ADVICE], true))
            || ($contextData['products'] === [] && $this->hasNoNarrative($contextData)
                && ! ($intent === AiIntent::UNSUPPORTED && $this->llm->available())
                && $intent !== AiIntent::GENERAL_FARMING
                && $intent !== AiIntent::FARMING_ADVICE);

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

        // A greeting that opened the turn is prepended to the answer so the
        // user feels heard before the actual response.
        $finalReply = $reply;
        if ($context->greetingPrefix !== null && $context->greetingPrefix !== '') {
            $finalReply = $context->greetingPrefix . "\n\n" . $reply;
        }

        $this->log($user, $intent, $contextData, $source, $violations);

        return [
            'reply'      => $finalReply,
            'type'       => $this->responseType($intent, $contextData),
            // Product cards, built from database rows. The frontend renders
            // these; it is never asked to parse them out of prose. A turn
            // whose only owed reply is the bare farm-area question shows no
            // cards — a price list above "what is your farm area?" reads as
            // an answer to a question nobody asked. When the crop's products
            // are still browsable (for example the season is the open slot)
            // the cards stay, because that is exactly what the farmer asked
            // to see.
            'suggestions' => array_values(array_unique($contextData['missing'])) === ['area']
                ? []
                : $contextData['products'],
            'context'    => $nextContext->toArray(),
            'intent'     => $intent,
            'source'     => $source,
            'violations' => $violations,
        ];
    }

    // ────────────────────────────────────────────────────────────
    //  Task guidance and stock answers (deterministic shortcuts)
    // ────────────────────────────────────────────────────────────

    /**
     * Walk the user through a task in their own account, in the order the
     * screen does it.
     *
     * The page catalog says WHERE a page is; this says HOW to do the thing.
     * Scoped by role, so a store owner gets store tasks, a farmer gets farm
     * tasks, and an admin gets oversight tasks — never another role's.
     *
     * @return array{reply: string, type: string, suggestions: array<int, mixed>, context: array<string, mixed>, intent: string, source: string, violations: array<int, string>}|null
     */
    private function howToAnswer(string $message, string $role, AiConversationContext $context): ?array
    {
        // Only messages that actually ask how to do something reach the task
        // table — a plain inventory or product question must not be answered
        // with steps nobody asked for.
        $asksHow = preg_match('/\b(?:how|paano|pano|guide|me\s+to|steps?|gawin|gamitin)\b/iu', $message) === 1
            || preg_match('/\b(?:add|create|update|edit|submit|make|set|import|restore|register|invite|insert)\b/iu', $message) === 1;

        if (! $asksHow) {
            return null;
        }

        $task = $this->howToTask($message, $role);

        if ($task === null || ! \Illuminate\Support\Facades\Route::has($task['route'])) {
            return null;
        }

        $lines = ['**' . $task['label'] . '** — ' . ($this->wantsTagalog($message) ? 'ganito po:' : 'here is how:'), ''];

        foreach ($task['steps'] as $index => $step) {
            $lines[] = ($index + 1) . '. ' . $step;
        }

        $lines[] = '';
        $lines[] = 'Open the [' . $task['label'] . '](' . route($task['route']) . ') page to start.';

        return [
            'reply'       => implode("\n", $lines),
            'type'        => 'answer',
            'suggestions' => [],
            'context'     => $context->toArray(),
            'intent'      => AiIntent::SYSTEM_HELP,
            'source'      => 'conversation',
            'violations'  => [],
        ];
    }

    /**
     * The task table: one entry per role-owned task the assistant can teach.
     *
     * Each `match` is deliberately narrow — it must name the task's own
     * object (product, stock, harvest, quotation, member, store), so
     * "how do I plant corn?" never lands on a task that has nothing to do
     * with planting.
     *
     * @return array{id: string, match: string, label: string, route: string, steps: array<int, string>}|null
     */
    private function howToTask(string $message, string $role): ?array
    {
        $tasks = [];

        if ($role === 'store_owner') {
            $tasks = [
                [
                    'id'    => 'add_product',
                    'match' => '/\b(?:add|create|insert|new|import|ilagay|maglagay|lagay|mag[\s\-]?add|gumawa)\b[^.?!]{0,60}\b(?:product|item|produkto|listahan)\b'
                        . '|\b(?:product|item|produkto)\b[^.?!]{0,40}\b(?:add|create|new|insert|gawin)\b/iu',
                    'label' => 'Product Listings',
                    'route' => 'store.storefront.index',
                    'steps' => [
                        'Open **Product Listings** and press **Add Product**.',
                        'Choose **Create manually** for a single item, or **Import from file** (CSV/XLSX) for many at once.',
                        'Fill in the name, category, brand, price, and package.',
                        'Set the starting **stock quantity** and **reorder level** — the reorder level is what decides when the product appears as low stock.',
                        'Save. The product shows up in your storefront and inventory right away.',
                    ],
                ],
                [
                    'id'    => 'update_stock',
                    'match' => '/\b(?:update|change|edit|adjust|restore|set|bawas|babawasan|dagdag|dagdagan|mag[\s\-]?update|paano|pano)\b[^.?!]{0,50}\b(?:stock|stocks|quantity|reorder|inventory)\b'
                        . '|\b(?:stock|stocks)\b[^.?!]{0,30}\b(?:update|change|edit|how|paano|pano)\b/iu',
                    'label' => 'Inventory',
                    'route' => 'store.inventory.index',
                    'steps' => [
                        'Open **Inventory** and find the product — the search box is at the top.',
                        'Press **Update Stock** on that product\'s row.',
                        'Enter the new **stock quantity** and the **reorder level**, then save.',
                        'The low-stock and out-of-stock counts update immediately.',
                    ],
                ],
            ];
        } elseif ($role === 'farmer') {
            $tasks = [
                [
                    'id'    => 'harvest_report',
                    'match' => '/\b(?:how|paano|pano|submit|record|add|create|new|gumawa|gawa|ilagay|maglagay|mag[\s\-]?submit)\b[^.?!]{0,60}\b(?:harvest\s+report|report\s+harvest|harvest)\b/iu',
                    'label' => 'Submit Harvest Report',
                    'route' => 'farmer.harvests.create',
                    'steps' => [
                        'Open **Submit Harvest Report**.',
                        'Pick the crop and the season (Wet or Dry) the harvest belongs to.',
                        'Enter the land area, harvest date, planned output, actual yield, and actual expenses.',
                        'Save — the report feeds your Estimate vs Actual and crop performance charts.',
                    ],
                ],
                [
                    'id'    => 'quotation',
                    'match' => '/\b(?:how|paano|pano|create|make|new|gumawa|gawa|mag[\s\-]?gawa|start)\b[^.?!]{0,60}\b(?:quotation|quote|quotations|estimat)\b'
                        . '|\b(?:quotation|quote|estimat)\b[^.?!]{0,40}\b(?:create|make|new|gumawa|paano|pano|how)\b/iu',
                    'label' => 'My Cost Quotations',
                    'route' => 'farmer.quotations.request',
                    'steps' => [
                        'Open **My Cost Quotations** and start a new request.',
                        'Choose the store, then add the products you need.',
                        'Pick the season (Wet or Dry) and the **Valid Until** date — usually 3 to 7 days, so the prices stay current.',
                        'Save. You can reopen it any time for the itemized breakdown.',
                    ],
                ],
                [
                    'id'    => 'buy_supplies',
                    'match' => '/\b(?:how|paano|pano|where)\b[^.?!]{0,50}\b(?:buy|order|purchase|inquire|umorder|bumili)\b'
                        . '|\b(?:buy|order|purchase)\b[^.?!]{0,40}\bsupplies\b/iu',
                    'label' => 'Browse Supplies',
                    'route' => 'farmer.products.browse',
                    'steps' => [
                        'Open **Browse Supplies** and search for the product you need.',
                        'Tap the product to see its price, stock, and the store that carries it.',
                        'Send your inquiry from that page — the store sees it and replies with the current price.',
                    ],
                ],
            ];
        } elseif ($role === 'admin') {
            $tasks = [
                [
                    'id'    => 'add_member',
                    'match' => '/\b(?:how|paano|pano|add|create|new|magdagdag|invite|register)\b[^.?!]{0,60}\b(?:member|farmer|user|account)\b/iu',
                    'label' => 'Members',
                    'route' => 'admin.members.index',
                    'steps' => [
                        'Open **Members** and press **Add Member**.',
                        'Fill in the member details and save — new farmer accounts need your approval before they can log in.',
                        'Bringing a list? Use **Import Members** to upload many at once.',
                    ],
                ],
                [
                    'id'    => 'add_store',
                    'match' => '/\b(?:how|paano|pano|add|create|new|magdagdag)\b[^.?!]{0,60}\b(?:store|shop|seller)\b/iu',
                    'label' => 'Stores',
                    'route' => 'admin.stores.index',
                    'steps' => [
                        'Open **Stores** and press **Add Store**.',
                        'Fill in the store name, owner account, and details, then save.',
                        'The store owner can then log in and manage their own products and inventory.',
                    ],
                ],
            ];
        }

        foreach ($tasks as $task) {
            if (preg_match($task['match'], $message) === 1) {
                return $task;
            }
        }

        return null;
    }

    /**
     * Stock and inventory questions in any phrasing: "low stock", "low in
     * stock", "almost out of stock", "reorder", "show my inventory", "maubos".
     */
    private function isStockQuestion(string $message): bool
    {
        return preg_match(
            '/\b(?:low[\s\-]?stock(?:ed)?|stock[s]?\s+(?:is\s+|are\s+)?low|low\s+in\s+stock|reorder(?:ing|point|level)?|'
                . 'out[\s\-]?of[\s\-]?stock|running\s+out|almost\s+out|nearly\s+out|ma(?:u|g)?ubos|ubos(?:\s+na)?|'
                . 'kulang\s+sa\s+stock|inventor(?:y|ies)|stock\s+levels?|stock\s+status)\b/iu',
            $message
        ) === 1;
    }

    /**
     * The store's stock position, read with InventoryController's own rules.
     *
     * The page's metric cards count low stock as `quantity <= reorder level`
     * and out of stock as `quantity <= 0` or status `out_of_stock`. This
     * answer uses exactly those predicates — the previous query required a
     * non-null threshold and `status = active`, so it reported "nothing is
     * low" while the page showed a low-stock item and an out-of-stock one.
     *
     * @return array{reply: string, type: string, suggestions: array<int, mixed>, context: array<string, mixed>, intent: string, source: string, violations: array<int, string>}
     */
    private function storeStockAnswer(User $user, AiConversationContext $context, string $message): array
    {
        $products = \App\Models\Product::query()
            ->where('user_id', (int) $user->id)
            ->get();

        // Identical to InventoryController's metric cards.
        $out = $products->filter(fn ($p) => ($p->stock_quantity ?? 0) <= 0 || $p->status === 'out_of_stock');
        $low = $products->filter(fn ($p) => ($p->stock_quantity ?? 0) <= $p->reorder_threshold);
        $lowAlone = $low->reject(fn ($p) => $out->contains('id', $p->id));

        $inventory = '[Inventory](' . route('store.inventory.index') . ')';
        $tagalog = $this->wantsTagalog($message);
        $asksOut = preg_match('/\b(?:out[\s\-]?of[\s\-]?stock|running\s+out|almost\s+out|nearly\s+out|ma(?:u|g)?ubos|ubos)\b/iu', $message) === 1;

        // The products named in the prose below, as cards, so the chat can
        // draw a real product tile next to each one instead of a bare name.
        $namedProducts = $out->concat($lowAlone)->unique('id')->values();

        if ($products->isEmpty()) {
            $reply = $tagalog
                ? 'Wala pa kayong nakalistang produkto. I-add muna ang inyong mga item sa [Product Listings](' . route('store.storefront.index') . ') — doon po ang **Add Product** at **Import from file**.'
                : 'You have no products listed yet. Add your items on [Product Listings](' . route('store.storefront.index') . ') first — that page has **Add Product** and **Import from file**.';
        } elseif ($asksOut && $out->isEmpty()) {
            $reply = $tagalog
                ? 'Wala pa kayong **out of stock** na produkto ngayon.'
                : 'You have no **out of stock** products right now.';

            if ($lowAlone->isNotEmpty()) {
                $lines = ['', $tagalog ? 'May ' . $lowAlone->count() . ' low stock item pa:' : 'You do have ' . $lowAlone->count() . ' low stock item(s):'];

                foreach ($lowAlone as $p) {
                    $lines[] = '- **' . $p->name . '** — stock: ' . (int) ($p->stock_quantity ?? 0)
                        . ', reorder level: ' . (int) ($p->reorder_threshold ?? 0);
                }

                $reply .= implode("\n", $lines);
            }

            $reply .= "\n\n" . ($tagalog ? 'Buksan ninyo ang ' : 'Open your ') . $inventory
                . ($tagalog ? ' para sa detalye.' : ' for details.');
        } else {
            $lines = [
                ($tagalog ? 'Sa inyong inventory: ' : 'In your inventory: ')
                    . '**' . $products->count() . '** produkto — **' . $low->count() . '** low stock, **'
                    . $out->count() . '** out of stock.',
                '',
            ];

            if ($out->isNotEmpty()) {
                $lines[] = '**Out of stock (' . $out->count() . '):**';

                foreach ($out as $p) {
                    $lines[] = '- **' . $p->name . '** — stock: ' . (int) ($p->stock_quantity ?? 0);
                }

                $lines[] = '';
            }

            if ($lowAlone->isNotEmpty()) {
                $lines[] = '**Low stock (' . $lowAlone->count() . '):**';

                foreach ($lowAlone as $p) {
                    $lines[] = '- **' . $p->name . '** — stock: ' . (int) ($p->stock_quantity ?? 0)
                        . ', reorder level: ' . (int) ($p->reorder_threshold ?? 0);
                }

                $lines[] = '';
            }

            if ($out->isEmpty() && $lowAlone->isEmpty()) {
                $lines[] = $tagalog
                    ? 'Wala pang out of stock o low stock na item — maayos pa ang stock ninyo.'
                    : 'Nothing is out of stock or running low — your stock is in good shape.';
                $lines[] = '';
            }

            if ($low->count() > $lowAlone->count()) {
                // The page's low-stock card also counts zero-stock items, so
                // say so rather than let the two numbers look like a lie.
                $lines[] = $tagalog
                    ? '_Kabilang sa bilang ng low stock sa Inventory page ang mga out of stock na item._'
                    : '_The Inventory page counts out-of-stock items inside its low-stock number too._';
                $lines[] = '';
            }

            $lines[] = ($tagalog ? 'Buksan ninyo ang ' : 'Open your ') . $inventory
                . ($tagalog ? ' para tingnan ang buong stock at para mag-update.' : ' to see everything and to update stock levels.');

            $reply = implode("\n", $lines);
        }

        return [
            'reply'       => $reply,
            'type'        => 'answer',
            'suggestions' => [],
            // Cards for the products the prose above names, so the chat can
            // draw a real tile beside each one. Not `suggestions`: those draw
            // a separate carousel, which would repeat the same products.
            'products'    => $this->catalog->cards($namedProducts, $user),
            'context'     => $context->toArray(),
            'intent'      => AiIntent::SYSTEM_HELP,
            'source'      => 'conversation',
            'violations'  => [],
        ];
    }

    // ────────────────────────────────────────────────────────────
    //  Model step
    // ────────────────────────────────────────────────────────────

    /**
     * Does this message ask the store about its own business?
     *
     * The words are what the router reads; the decision to treat them as a
     * demand question belongs here, because only a store owner has demand to
     * be asked about. A question about a specific product's price or a cost
     * estimate is left alone — those are catalog questions with their own
     * answers.
     *
     * @return bool
     */
    private function isStoreDemandQuestion(string $message, string $intent): bool
    {
        // A price, a cost or a specific product is the catalog's business.
        if (in_array($intent, [
            AiIntent::COST_ESTIMATION,
            AiIntent::PRODUCT_SEARCH,
        ], true)) {
            return false;
        }

        $text = mb_strtolower($message);

        foreach (self::STORE_DEMAND_PATTERNS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }

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
        $answer = $fallback;
        $violations = [];
        $source = 'fallback';
        $feedback = '';
        $validated = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $userContent = AquilalaAssistantPrompt::user($contextData, $message);

            if ($feedback !== '') {
                $userContent .= "\n\nYour previous answer was rejected because: " . $feedback
                    . "\nWrite a corrected answer that actually responds to the question.";
            }

            $completion = $this->llm->completeJson([
                ['role' => 'system', 'content' => AquilalaAssistantPrompt::system($user?->role)],
                ['role' => 'user', 'content' => $userContent],
            ]);

            if ($completion === null) {
                // The provider could not be reached, or returned nothing
                // usable. The deterministic answer is already in $answer
                // (it started as $fallback). Keep it: the farmer gets a
                // correct, verified answer even when the model is offline.
                // Only report 'unavailable' when there is no fallback at all.
                if ($this->llm->available() && $answer === '' && $fallback === '') {
                    $source = 'unavailable';
                }

                break;
            }

            $decoded = $this->validator->parse($completion['raw']);

            if ($decoded === null) {
                $violations = [AiResponseValidator::VIOLATION_MALFORMED];
                break;
            }

            $validated = $this->validator->validate(
                $decoded,
                $contextData['products'],
                $fallback,
                $this->trustedCropNames($contextData),
            );

            $candidate = $validated['answer'] !== '' ? $validated['answer'] : $fallback;
            $candidate = $this->dedupeRepeatedLines($candidate);

            if ($this->isMetaAnswer($candidate)) {
                $candidate = $fallback;
            }

            if ($validated['needs_clarification'] || $validated['violations'] !== []) {
                $answer = $fallback;
                $violations = $validated['violations'];
                break;
            }

            $answer = $candidate;
            $violations = $validated['violations'];
            $source = 'ai';

            // Run AI2 validator for ALL intents that produce user-facing answers
            // (not just the previously limited set). This catches:
            // - Data dumping (harvest reports dumping all years)
            // - Irrelevant answers (greeting → harvest report)
            // - Meta-answers (capability lists instead of help)
            // - Missing conversational awareness
            if ($answer !== $fallback && $answer !== '') {
                if (in_array($intent, [
                    AiIntent::GREETING,
                    AiIntent::SYSTEM_HELP,
                    AiIntent::UNSUPPORTED,
                    AiIntent::GENERAL_FARMING,
                    AiIntent::FARMING_ADVICE,
                    AiIntent::CROP_ADVICE,
                    AiIntent::CROP_DISCOVERY,
                    AiIntent::CROP_RECOMMENDATION,
                    AiIntent::SEASONAL_RECOMMENDATION,
                    AiIntent::PRODUCT_SEARCH,
                    AiIntent::PRODUCT_RECOMMENDATION,
                    AiIntent::COST_ESTIMATION,
                    AiIntent::STORE_DEMAND,
                ], true)) {
                    $ai2 = $this->validatorAi->validate(
                        $message,
                        $user?->role ?? 'guest',
                        $answer,
                        $contextData,
                    );

                    if ($ai2 === null) {
                        break;
                    }

                    if ($ai2['verdict'] === AiResponseValidatorAi::VERDICT_ACCEPT) {
                        break;
                    }

                    if ($ai2['verdict'] === AiResponseValidatorAi::VERDICT_REVISE) {
                        $answer = $ai2['suggested_answer'] !== '' && $ai2['suggested_answer'] !== null
                            ? $ai2['suggested_answer']
                            : $fallback;
                        break;
                    }

                    $feedback = $ai2['reason'] ?? 'the answer did not address the user\'s question';

                    if ($attempt === 3) {
                        $answer = $fallback;
                    }
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        if ($answer === null || $answer === '') {
            $answer = $fallback;
        }

        return [
            'answer'     => $answer,
            'violations' => $violations,
            'source'     => $source === 'unavailable'
                ? 'unavailable'
                : ($source === 'fallback' || $answer === $fallback ? 'fallback' : 'ai'),
        ];


    }

    /**
     * The one honest answer when nobody could answer: the provider could not
     * be reached in time, or returned nothing the pipeline could use. The
     * farmer gets a clear message to try again — never a guess dressed up as
     * the model's answer, and never the deterministic fallback in its place.
     */
    private function unavailableReply(): string
    {
        return 'AI is temporarily unavailable right now. Please check your internet connection and try again in a moment.';
    }

    /**
     * Is this an answer about the assistant rather than about the subject?
     *
     * The model is told not to describe itself, and mostly listens. When it
     * does not, the answer is recognisable: it talks about capabilities,
     * instructions, context or access rather than about crops, products or
     * prices.
     */
    private function isMetaAnswer(string $answer): bool
    {
        $text = mb_strtolower($answer);

        foreach (self::META_ANSWER_MARKERS as $marker) {
            if (str_contains($text, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove lines the model said more than once.
     *
     * A line counts as repeated when it says the same thing as an earlier
     * line, ignoring bullet markers, emphasis, punctuation and case. The
     * first copy stays where it is; later copies are dropped.
     */
    private function dedupeRepeatedLines(string $answer): string
    {
        $lines = preg_split('/\R/', $answer);
        if ($lines === false) {
            return $answer;
        }

        $seen = [];
        $kept = [];

        foreach ($lines as $line) {
            $key = $this->lineKey($line);

            if ($key !== '') {
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
            }

            $kept[] = $line;
        }

        return implode("\n", $kept);
    }

    /**
     * A comparable form of a line: the words, with the decoration taken off.
     */
    private function lineKey(string $line): string
    {
        $text = trim($line);
        if ($text === '') {
            return '';
        }

        // Bullet and heading markers, emphasis, code ticks and links.
        $text = preg_replace('/^\s*(?:[-*+]|\d+[.)]|#{1,6})\s+/u', '', $text) ?? $text;
        $text = str_replace(['**', '__', '*', '`', '#'], '', $text);
        $text = preg_replace('/\[([^\]]*)\]\([^)]*\)/u', '$1', $text) ?? $text;
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
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
    private function deterministicAnswer(string $intent, array $contextData, array $routing, ?User $user = null, string $message = ''): string
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
            AiIntent::STORE_DEMAND => $this->storeDemandAnswer($contextData),
            AiIntent::CROP_ADVICE => $this->cropAdviceAnswer($crop, $missing, $routing),
            AiIntent::GREETING => $this->greetingAnswer($contextData),
            AiIntent::CROP_DISCOVERY,
            AiIntent::CROP_SEARCH => $this->cropDiscoveryAnswer($contextData, $routing),
            AiIntent::CROP_RECOMMENDATION => $this->cropChoiceAnswer($contextData, $routing),
            AiIntent::SEASONAL_RECOMMENDATION => $this->seasonalAnswer($contextData, $routing),
            AiIntent::FARMING_ADVICE => $this->farmingAdviceAnswer($contextData, $crop, $missing, $routing),
            AiIntent::GENERAL_FARMING => $this->generalAnswer($contextData, (string) ($user?->role ?? $contextData['facts']['role'] ?? '')),
            AiIntent::SYSTEM_HELP => $this->helpAnswer($contextData),
            AiIntent::UNSUPPORTED => $this->unsupportedAnswer($user?->role ?? ($contextData['facts']['role'] ?? null), $message),
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
                : $this->seasonalCropChoicePrefix($contextData)
                    . 'Are you after a **fruit crop**, a **vegetable**, or a **rice or corn** planting? '
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
                . 'I am not giving you an exact planting distance, seed rate or spray schedule — those come from the '
                . 'association\'s decision formulas, which need your crop, season and farm area. '
                . 'Aquilala\'s own numbers for **' . $crop . '** are below.';

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

        $lines = ['You are keen to get planting — that is a great place to start.', ''];

        if ($crops !== []) {
            $lines[] = 'Tell me which crop you have in mind and I can give you its Aquilala record, plus the general planting process for it.';
            $lines[] = '';
            $lines[] = 'Mga tanim na pwede: ' . implode(', ', array_slice(array_map(
                static fn (array $entry) => (string) $entry['name'],
                $crops
            ), 0, 15)) . '.';
        } else {
            $lines[] = 'Tell me the crop you are growing and I will give you its Aquilala record.';
        }

        $lines[] = '';
        $lines[] = 'What crop are you thinking of?';

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
        // Something required is missing, so there is no recommendation to
        // give yet. The question is the whole answer here: a product list
        // with prices sitting above "what is your farm area?" looks like the
        // system already priced a farm it knows nothing about.
        $pendingQuestion = $this->clarificationQuestion($missing, $crop, $routing);

        if ($pendingQuestion !== null) {
            return $pendingQuestion;
        }

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

        // An expired estimate is history. Say so plainly, instead of sending
        // the reader off to act on a price that has since moved.
        if (! empty($quotation['is_expired'])) {
            $lines[] = '**This estimate has expired** (its window closed on ' . $quotation['valid_until'] . '). '
                . 'The figures are the ones it was made with and the prices may have moved since. '
                . 'Generate a new estimate for current prices and stock.';
            $lines[] = '';
        }

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
            return "Wala po akong tala para sa **{$crop}** sa Aquilala, ngunit pwede kitang tulungan batay sa "
                . 'karaniwang kaalaman sa pagsasaka. Sabihin niyo po kung anong tanim talaga ang ibig niyong alamin.';
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
     * A store owner's own demand, in their own numbers.
     *
     * The store's quotations are the only source for this, so the answer is
     * built from them and never padded with a capability list. A store owner
     * asking what is trending wants the products and the crops, not a
     * description of the assistant.
     *
     * @param  array<string, mixed>  $contextData
     */
    private function storeDemandAnswer(array $contextData): string
    {
        $demand = $contextData['facts']['store_demand'] ?? null;

        if (! is_array($demand) || ($demand['total_quotations'] ?? 0) === 0) {
            return 'No farmer has quoted your products yet, so there is nothing trending to report. '
                . 'As soon as a farmer creates a quotation that includes your items, the numbers land here.';
        }

        $total = (int) $demand['total_quotations'];
        $farmers = (int) $demand['unique_farmers'];
        $thisWeek = (int) $demand['this_week'];
        $lastWeek = (int) $demand['last_week'];
        $products = $demand['top_products'] ?? [];
        $crops = $demand['top_crops'] ?? [];

        $lines = [];

        $lines[] = $total . ' farmer quotation' . ($total === 1 ? '' : 's')
            . ' from ' . $farmers . ' farmer' . ($farmers === 1 ? '' : 's')
            . ' include your products.';

        if ($thisWeek > 0 || $lastWeek > 0) {
            $lines[] = 'This week: **' . $thisWeek . '**, last week: **' . $lastWeek . '**.';
        }

        if ($products !== []) {
            $lines[] = '';
            $lines[] = '### Most quoted products';
            foreach (array_slice($products, 0, 5) as $product) {
                $lines[] = '- **' . $product['name'] . '** — ' . $product['count'] . ' quotation'
                    . ($product['count'] === 1 ? '' : 's')
                    . ', ₱' . number_format((float) $product['value'], 0) . ' quoted.';
            }
        }

        if ($crops !== []) {
            $lines[] = '';
            $lines[] = '### Crops in demand';
            foreach (array_slice($crops, 0, 3) as $crop) {
                $lines[] = '- **' . $crop['crop'] . '** — ' . $crop['count'] . ' quotation'
                    . ($crop['count'] === 1 ? '' : 's')
                    . ' over ' . round((float) $crop['area'], 1) . ' ha.';
            }
        }

        if ($products !== []) {
            $lines[] = '';
            $lines[] = 'Keep **' . $products[0]['name'] . '** well stocked — it is what farmers quote most.';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $contextData
     */
    private function generalAnswer(array $contextData, string $role = ''): string
    {
        $facts = $contextData['facts'] ?? [];
        $season = $facts['current_season'] ?? [];

        $lines = [];

        if ($role === 'store_owner') {
            $lines[] = 'Ask me about your store inventory, top products, quotation demand trends, product listings, or pricing, '
                . 'and I will help you with those.';
            return implode("\n", $lines);
        }

        if (! empty($season['label'])) {
            $lines[] = 'It is **' . $season['label'] . '**' . ($season['typical_weather'] ? ' — ' . $season['typical_weather'] : '') . '.';
            $lines[] = '';
        }

        $lines[] = 'Ask me about a crop, a product, a price, a cost estimate, or how to grow something, '
            . 'and I will answer it.';

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $contextData
     */
    private function helpAnswer(array $contextData): string
    {
        $crops = $contextData['facts']['supported_crops'] ?? [];

        $lines = [
            'Ask me anything about farming and inputs, for example:',
            '',
            '- "Do you have urea products?"',
            '- "What fertilizers for rice?"',
            '- "How much would 2 hectares of corn cost?"',
            '- "How do I plant corn?"',
        ];

        if ($crops !== []) {
            $lines[] = '';
            $lines[] = 'Crops Aquilala has formulas for: ' . implode(', ', $crops) . '.';
        }

        return implode("\n", $lines);
    }

    /**
     * The out-of-scope answer. It never reads as a dead end: it names what
     * this role CAN ask about, including how to use their own account pages,
     * and it answers in the language the person wrote in.
     */
    private function unsupportedAnswer(?string $role = null, string $message = ''): string
    {
        $tagalog = $this->wantsTagalog($message);

        if ($role === 'admin') {
            return $tagalog
                ? 'Sa admin account, gabayan ko po kayo sa member management, store at product oversight, harvest records, at reports — kung paano gumawa ng isang task sa inyong account. Tanong niyo po tungkol doon.'
                : 'I can guide an admin through member management, store and product oversight, harvest records, and reports — including how to do any task in your account. Ask me about those.';
        }

        if ($role === 'store_owner') {
            return $tagalog
                ? 'Para sa inyong tindahan, masasagot ko sa inventory, produkto, demand trends, top products, at prices — at kung paano gamitin ang mga pahina ng inyong account. Tanong niyo po tungkol doon.'
                : 'I can help with your store inventory, products, demand trends, top products, and prices — and with how to use any page in your store account. Ask me about those.';
        }

        return $tagalog
            ? 'Tungkol sa pagsasaka at produkto ng Aquilala ang masasagot ko — tanim, presyo, gastusin sa bukid, at kung paano gamitin ang mga pahina ng account ninyo. Tanong niyo po tungkol doon.'
            : 'I can help with farming, Aquilala products, prices, cost estimates, and with how to use the pages in your account. Ask me about any of those.';
    }

    /**
     * Does this message read as Tagalog or Taglish?
     *
     * Only the fixed shortcut answers use this — they cannot ask a model to
     * match the language the way the explanation step does.
     */
    private function wantsTagalog(string $message): bool
    {
        return preg_match(
            '/\b(?:pano|paano|ako|ng|mga|ang|wala|ito|yung|gusto|salamat|ninyo|niyo|inyo|tindahan|presyo|tanim|'
                . 'ubos|maubos|magubos|ilagay|gawin|gamitin|tanungin|sabihin|po|ba)\b/iu',
            $message
        ) === 1;
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
            // Naming the crop keeps the turn about the farmer's own question
            // — they asked about rice, so the question about rice.
            return ($crop !== null && $crop !== '')
                ? 'I can work out the recommendation for **' . $crop . '**. What is your farm area in hectares?'
                : 'I can help work that out for you. What is your farm area in hectares?';
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
    private function pageLinkReply(string $message, string $role): ?array
    {
        $pages = $this->assistantPageCatalog($role);
        if ($pages === []) {
            return null;
        }

        $lower = mb_strtolower(trim($message));

        $hasTrigger = str_contains($lower, 'page')
            || str_contains($lower, 'where')
            || str_contains($lower, 'find')
            || str_contains($lower, 'link')
            || str_contains($lower, 'open')
            || str_contains($lower, 'navigate')
            || str_contains($lower, 'menu')
            || str_contains($lower, 'location')
            || str_contains($lower, 'go to')
            || str_contains($lower, 'take me')
            || str_contains($lower, 'pano')
            || str_contains($lower, 'paano')
            || str_contains($lower, 'makikita')
            || str_contains($lower, 'saan')
            || str_contains($lower, 'punntahan')
            || str_contains($lower, 'show')
            || str_contains($lower, 'list')
            || str_contains($lower, 'tell me')
            || str_contains($lower, 'add')
            || str_contains($lower, 'create')
            || str_contains($lower, 'manage');

        if (! $hasTrigger) {
            return null;
        }

        $messageWords = array_values(array_unique(preg_split('/\s+/u', preg_replace('/[^a-z0-9\s]+/iu', ' ', $lower) ?? '') ?: []));
        $matches = [];

        foreach ($pages as $label => $routeName) {
            $labelWords = array_values(array_unique(preg_split('/\s+/u', preg_replace('/[^a-z0-9\s]+/iu', ' ', mb_strtolower($label)) ?? '') ?: []));
            $score = count(array_intersect($labelWords, $messageWords));

            if ($score > 0) {
                $matches[$label] = ['route' => $routeName, 'score' => $score];
            }
        }

        if ($matches === []) {
            return null;
        }

        uasort($matches, fn (array $left, array $right): int => $right['score'] <=> $left['score']);
        $matches = array_slice($matches, 0, 8, true);

        $descriptions = [
            'Dashboard' => 'where you can see your overview.',
            'Submit Harvest Report' => 'where you can add your own harvest report.',
            'My Crops' => 'where you can manage your crop batches.',
            'Harvest Reports' => 'where you can view your harvest history and reports.',
            'Estimate vs Actual Harvest' => 'where you can compare your estimated and reported harvest.',
            'My Cost Quotations' => 'where you can create and review your cost quotations.',
            'Browse Supplies' => 'where you can browse available farming supplies.',
            'Recommended Supplies' => 'where you can see recommendation results.',
            'Top Quoted Products' => 'where you can check frequently quoted products.',
            'Available Stores' => 'where you can find stores.',
            'Crop Guide' => 'where you can check available crops and guidance.',
            'AI Assistant' => 'where you can chat with the AI assistant.',
            'Calendar' => 'where you can view the association calendar.',
            'Events' => 'where you can check association events.',
            'Account Settings' => 'where you can manage your account settings.',
            'Dashboard' => 'where you can see your overview.',
            'Top Products' => 'where you can check product performance.',
            'Product Listings' => 'where you can manage your product listings.',
            'Inventory' => 'where you can manage your store inventory.',
            'Input Formulas' => 'where you can manage farm input formulas.',
            'Quotation Demand Trends' => 'where you can see quotation demand trends.',
            'Settings' => 'where you can manage your account settings.',
            'Activity History' => 'where you can check your activity history.',
        ];

        $links = [];

        foreach ($matches as $label => $details) {
            try {
                $url = route((string) $details['route']);
            } catch (\Throwable $e) {
                continue;
            }

            // Only the clickable link text is shown; the URL stays hidden
            // behind it so the message stays short and easy to tap.
            $links[] = '- **[' . $label . '](' . $url . ')**';
        }

        if ($links === []) {
            return null;
        }

        $intro = $this->wantsTagalog($message)
            ? 'Narito ang mga pahina na tumutugma sa hinahanap ninyo. Buksan angkop sa inyong kailangan:'
            : 'Here are the page links that match your request. Open the one that fits what you need:';

        return [
            'reply'  => $intro . "\n" . implode("\n", $links),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function assistantPageCatalog(string $role): array
    {
        $pages = [];

        if ($role === 'farmer') {
            $pages = [
                'Dashboard' => 'dashboard.farmer',
                'Home' => 'farmer.dashboard',
                'Submit Harvest Report' => 'farmer.harvests.create',
                'My Crops' => 'farmer.crops.index',
                'Harvest Reports' => 'farmer.harvests.index',
                'Estimate vs Actual Harvest' => 'farmer.harvests.compare',
                'My Cost Quotations' => 'farmer.quotations.index',
                'Browse Supplies' => 'farmer.products.browse',
                'Recommended Supplies' => 'farmer.recommendations.index',
                'Top Quoted Products' => 'farmer.top-quoted.index',
                'Available Stores' => 'farmer.stores.index',
                'Crop Guide' => 'farmer.crops-directory.index',
                'AI Assistant' => 'farmer.ai-chat.index',
                'Calendar' => 'farmer.calendar.index',
                'Events' => 'farmer.calendar.events',
                'Account Settings' => 'farmer.settings.index',
            ];
        } elseif ($role === 'store_owner') {
            $pages = [
                'Dashboard' => 'store.dashboard',
                'Top Products' => 'store.dashboard.performance',
                'Product Listings' => 'store.storefront.index',
                'Inventory' => 'store.inventory.index',
                'Input Formulas' => 'store.formulas.index',
                'Quotation Demand Trends' => 'store.quotations.index',
                'AI Assistant' => 'store.ai-chat.index',
                'Settings' => 'store.settings.index',
                'Activity History' => 'store.activity.index',
            ];
        } elseif ($role === 'admin') {
            $pages = [
                'Dashboard' => 'admin.dashboard',
                'Members' => 'admin.members.index',
                'Users' => 'admin.users.index',
                'Stores' => 'admin.stores.index',
                'Quotations' => 'admin.quotations.index',
                'Harvest Records' => 'admin.harvests.index',
                'AI Harvest Chat' => 'admin.harvests.ai',
                'Analytics' => 'admin.analytics.index',
                'Calendar' => 'admin.calendar.index',
                'Activity History' => 'admin.activity.index',
                'Settings' => 'admin.settings.index',
                'AI Assistant' => 'admin.ai-chat.index',
            ];
        }

        $available = [];

        foreach ($pages as $label => $routeName) {
            if (\Illuminate\Support\Facades\Route::has($routeName)) {
                $available[$label] = $routeName;
            }
        }

        return $available;
    }

    private function weatherSummary(string $message): string
    {
        $weather = \App\Services\WeatherService::getCurrentWeather();

        if (empty($weather['is_available'])) {
            return 'Current weather for **Bulan, Sorsogon** is temporarily unavailable. Please try again in a moment.';
        }

        $temperature = isset($weather['temperature']) ? round((float) $weather['temperature'], 1) . '°C' : 'n/a';
        $condition = (string) ($weather['condition'] ?? 'unknown conditions');
        $humidity = $weather['humidity'] ?? null;
        $wind = $weather['wind_speed'] ?? null;
        $updated = (string) ($weather['updated_at'] ?? '');

        $line = '**Bulan, Sorsogon** right now: **' . $temperature . ' · ' . $condition . '**'
            . ($humidity !== null ? ', humidity **' . $humidity . '%**' : '')
            . ($wind !== null ? ', wind **' . $wind . ' km/h**' : '')
            . ($updated !== '' ? ' (updated ' . $updated . ')' : '') . '.';

        return $line;
    }

    /**
     * @return array{reply: string, type: string, suggestions: array<int,mixed>, context: array<string,mixed>, intent: string, source: string, violations: array<int,string>}
     */
    private function harvestOverviewAnswer(string $message, ?\App\Models\User $user): array
    {
        $makeResponse = fn (string $reply): array => [
            'reply'       => $reply,
            'type'        => 'answer',
            'suggestions' => [],
            'context'     => [],
            'intent'      => \App\AI\AiIntent::SYSTEM_HELP,
            'source'      => 'conversation',
            'violations'  => [],
        ];

        $role = $user?->role ?? '';

        if ($role !== 'farmer') {
            // Not a refusal — this account simply has other pages. Point at
            // the ones it does have instead of ending the conversation.
            if ($role === 'store_owner') {
                return $makeResponse(
                    'Harvest reports belong to farmer members, so your store account has none. '
                    . 'For your own store you can check the [Dashboard](' . route('store.dashboard') . ') '
                    . 'and [Quotation Demand Trends](' . route('store.quotations.index') . '). '
                    . 'Tell me what you would like to see in your store and I will guide you.'
                );
            }

            if ($role === 'admin') {
                return $makeResponse(
                    'There is no personal harvest overview in an admin account. '
                    . 'Open the [Harvest Records](' . route('admin.harvests.index') . ') page for the association-wide data, '
                    . 'or the [AI Harvest Chat](' . route('admin.harvests.ai') . ') to ask about it in plain language. '
                    . 'Ask me to show your pages any time.'
                );
            }

            return $makeResponse(
                'There is no harvest overview in this account. '
                . 'Ask me to show the pages you need and I will point you to them.'
            );
        }

        $year = null;
        $periodType = 'all'; // all, year, month, week, today

        if (preg_match('/\b(20\d{2})\b/u', $message, $match) === 1) {
            $year = (int) $match[1];
            $periodType = 'year';
        } elseif (preg_match('/\bthis\s+(?:year|month)\b/iu', $message) === 1) {
            $year = (int) now()->year;
            $periodType = 'month';
        } elseif (preg_match('/\b(this\s+week|past\s+week|last\s+week)\b/iu', $message) === 1) {
            $periodType = 'week';
        } elseif (preg_match('/\b(today|this\s+day|recent|latest|new|activity)\b/iu', $message) === 1) {
            $periodType = 'today';
        }

        $query = \App\Models\HarvestReport::where('user_id', $user->id);

        $now = now();
        switch ($periodType) {
            case 'year':
                $query->whereYear('harvest_date', $year);
                $period = "in $year";
                break;
            case 'month':
                $query->whereYear('harvest_date', $now->year)
                      ->whereMonth('harvest_date', $now->month);
                $period = "this month (" . $now->format('F Y') . ")";
                break;
            case 'week':
                $query->whereBetween('harvest_date', [
                    $now->copy()->startOfWeek(),
                    $now->copy()->endOfWeek()
                ]);
                $period = "this week (" . $now->copy()->startOfWeek()->format('M d') . " - " . $now->copy()->endOfWeek()->format('M d, Y') . ")";
                break;
            case 'today':
                $query->whereDate('harvest_date', $now->toDateString());
                $period = "today (" . $now->format('F d, Y') . ")";
                break;
            default:
                $period = 'so far';
                break;
        }

        $reports = $query->get();

        if ($reports->isEmpty()) {
            return $makeResponse("You have no harvest reports recorded $period. "
                . "You can submit one on the [My Harvest Reports](" . route('farmer.harvests.index') . ") page.");
        }

        $totalArea = (float) $reports->sum('land_area');
        $totalYield = (float) $reports->sum('actual_yield_metric_tons');
        $yieldPerHectare = $totalArea > 0 ? round($totalYield / $totalArea, 3) : null;
        $crops = $reports->pluck('crop_type')->filter()->unique()->sort()->values()->all();

        $lines = [
            'Here is your harvest overview ' . $period . ':',
            '',
            '**' . number_format($reports->count()) . '** report' . ($reports->count() === 1 ? '' : 's') . ' across **' . count($crops) . '** crop'
                . (count($crops) === 1 ? '' : 's') . ', covering **' . number_format($totalArea, 2) . ' ha**, '
                . '**' . number_format($totalYield, 2) . ' MT** total yield'
                . ($yieldPerHectare !== null ? ' (**' . $yieldPerHectare . ' MT/ha**)' : '') . '.',
        ];

        if ($crops !== []) {
            $lines[] = '**Crops:** ' . implode(', ', $crops) . '.';
        }

        // Show recent reports for "today" or "week" queries
        if (in_array($periodType, ['today', 'week'])) {
            $recent = $reports->sortByDesc('harvest_date')->take(5);
            $lines[] = '';
            $lines[] = '**Recent harvests:**';
            foreach ($recent as $r) {
                $lines[] = '- ' . $r->crop_type . ' — ' . number_format($r->land_area, 2) . ' ha, ' 
                    . number_format($r->actual_yield_metric_tons, 2) . ' MT on ' . $r->harvest_date->format('M d, Y');
            }
        }

        $lines[] = '';
        $lines[] = 'See the full detail on your [My Harvest Reports](' . route('farmer.harvests.index') . ') page.';

        return $makeResponse(implode("\n", $lines));
    }

    private function assistantIsGreetingFlexible(string $message): bool
    {
        $normalized = trim((string) preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $message));
        $normalized = trim((string) preg_replace('/\s+/u', ' ', $normalized));

        if ($normalized === '' || mb_strlen($normalized) > 45) {
            return false;
        }

        return preg_match(
            '/\b(good\s+(morning|afternoon|evening|day|eve\w*)|hello|hi|hey|greetings|morning|afternoon|evening|eve\w*|how\s+are\s+you|kumusta)\b/iu',
            $normalized
        ) === 1;
    }

    private function assistantGreetingReply(string $message, array $contextData): string
    {
        $normalized = mb_strtolower(trim((string) preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $message)));

        if (str_contains($normalized, 'evening')) {
            $base = 'Good evening! How can I help with your farm today?';
        } elseif (str_contains($normalized, 'afternoon')) {
            $base = 'Good afternoon! How can I help with your farm today?';
        } elseif (str_contains($normalized, 'morning')) {
            $base = 'Good morning! How can I help with your farm today?';
        } else {
            $base = 'Hello! How can I help with your farm today?';
        }

        $season = $contextData['facts']['season_profile'] ?? [];

        if (! empty($season['label'])) {
            return $base . ' It is **' . $season['label'] . '** in Bulan right now.';
        }

        return $base;
    }

    /**
     * True when an administrator asks about recent or today's farmer
     * activity — planting, crop updates, land changes, harvest records.
     */
    private function isAdminFarmerActivityQuestion(string $message): bool
    {
        return preg_match(
            '/\b(?:activity|activities|action|actions|update|updates|submission|submissions|recent|today|this\s+(?:week|month)|what\s+(?:have|are|did|has|have\s+been))\b/iu',
            $message
        ) === 1;
    }

    /**
     * Answer an administrator's question about recent farmer activity from
     * the audit log, association-wide (not just this admin's own reports).
     *
     * @return array{reply: string, type: string, suggestions: array<int, mixed>, context: array<string, mixed>, intent: string, source: string, violations: array<int, string>}
     */
    private function adminFarmerActivityAnswer(string $message): array
    {
        $timezone = config('app.timezone');
        [$start, $end, $label] = $this->activityWindow($message, $timezone);

        $activities = \App\Models\ActivityLog::query()
            ->with(['actor', 'user'])
            ->whereIn('type', [
                \App\Models\ActivityLog::TYPE_CROP_PLANTED,
                \App\Models\ActivityLog::TYPE_CROP_UPDATED,
                \App\Models\ActivityLog::TYPE_CROP_HARVESTED,
                \App\Models\ActivityLog::TYPE_CROP_DELETED,
                \App\Models\ActivityLog::TYPE_LAND_UPDATED,
                \App\Models\ActivityLog::TYPE_HARVEST_CREATED,
                \App\Models\ActivityLog::TYPE_HARVEST_DELETED,
            ])
            ->where(function ($query) {
                $query->whereHas('actor', fn ($actor) => $actor->where('role', 'farmer'))
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('actor_id')
                            ->whereHas('user', fn ($user) => $user->where('role', 'farmer'));
                    });
            })
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->latest('created_at')
            ->get();

        $rows = $activities->map(function (\App\Models\ActivityLog $activity): array {
            $farmer = $activity->actor ?? $activity->user;
            $farmerName = $farmer
                ? trim(implode(' ', array_filter([$farmer->first_name, $farmer->last_name])))
                : '';

            return [
                'farmer' => $farmer?->name ?: ($farmerName ?: 'A farmer'),
                'type' => $activity->type,
                'description' => $activity->description,
                'recorded_at' => $activity->created_at?->timezone($timezone)->format('g:i A'),
            ];
        })->values()->all();

        $rows = array_values(array_unique($rows, SORT_REGULAR));
        $rows = collect($rows)->unique(fn (array $row) => $row['farmer'] . '|' . $row['description'])->values()->all();

        if ($rows === []) {
            return [
                'reply'       => 'I checked the farmer activity records for **' . $label . '**, and no new planting, crop, land, or harvest activity has been recorded yet.',
                'type'        => 'answer',
                'suggestions' => [],
                'context'     => [],
                'intent'      => AiIntent::SYSTEM_HELP,
                'source'      => 'data',
                'violations'  => [],
            ];
        }

        $shown = array_slice($rows, 0, 8);
        $hidden = count($rows) - count($shown);

        $lines = collect($shown)->map(
            fn (array $row) => '- **' . $row['farmer'] . '**: ' . $row['description']
                . ($row['recorded_at'] ? ' (' . $row['recorded_at'] . ')' : '')
        )->implode("\n");

        $reply = 'I found **' . number_format(count($rows)) . '** farmer '
            . (count($rows) === 1 ? 'activity' : 'activities') . ' recorded in **' . $label . '**:'
            . "\n" . $lines;

        if ($hidden > 0) {
            $reply .= "\n…plus " . number_format($hidden) . " more recent entr" . ($hidden === 1 ? 'y' : 'ies') . ".";
        }

        return [
            'reply'       => $reply,
            'type'        => 'answer',
            'suggestions' => [],
            'context'     => ['farmer_activity' => ['period' => $label, 'count' => count($rows), 'items' => $rows]],
            'intent'      => AiIntent::SYSTEM_HELP,
            'source'      => 'data',
            'violations'  => [],
        ];
    }

    /**
     * Resolve the time window for an activity question.
     */
    private function activityWindow(string $message, string $timezone): array
    {
        $now = \Illuminate\Support\Carbon::now($timezone);
        $lower = mb_strtolower($message);

        if (preg_match('/\byesterday\b/u', $lower) === 1) {
            $start = $now->copy()->subDay()->startOfDay();
            $end = $start->copy()->addDay();

            return [$start, $end, 'Yesterday'];
        }

        if (preg_match('/\btoday\b/u', $lower) === 1) {
            $start = $now->copy()->startOfDay();
            $end = $start->copy()->addDay();

            return [$start, $end, 'Today'];
        }

        if (preg_match('/\bthis\s+week\b/u', $lower) === 1) {
            $start = $now->copy()->startOfWeek();
            $end = $start->copy()->addWeek();

            return [$start, $end, 'This Week'];
        }

        if (preg_match('/\blast\s+week\b/u', $lower) === 1) {
            $start = $now->copy()->subWeek()->startOfWeek();
            $end = $start->copy()->addWeek();

            return [$start, $end, 'Last Week'];
        }

        if (preg_match('/\bthis\s+month\b/u', $lower) === 1 || preg_match('/\bmonth\b/u', $lower) === 1) {
            $start = $now->copy()->startOfMonth();
            $end = $start->copy()->addMonth();

            return [$start, $end, $now->format('F Y')];
        }

        // Default: today.
        $start = $now->copy()->startOfDay();
        $end = $start->copy()->addDay();

        return [$start, $end, 'Today'];
    }

    private function assistantIsSystemHelp(string $message): bool
    {
        $normalized = trim((string) preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $message));

        return trim($normalized) !== '' && (
            preg_match('/\b(how\s+(?:do|can|to)\s+(?:i|we|you)?\s*(?:use|navigate|find|open|get\s+started)|what\s+(?:can|does)\s+(?:this|you|ai)?\s*(?:do|help)|how\s+does\s+(?:this|it)\s+work|use\s+this|this\s+system|are\s+you\s+(?:working|there|online|ai)|ai\s+ka\s+ba|is\s+this\s+working)\b/iu', $normalized) === 1
            || trim($normalized) === 'how do i use the this'
        );
    }

    /**
     * Detect a greeting at the start of a message and return a matching
     * greeting reply. Returns null when the message does not open with a
     * greeting.
     */
    private function extractGreetingPrefix(string $message): ?string
    {
        $normalized = mb_strtolower(trim((string) preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $message)));
        $normalized = trim((string) preg_replace('/\s+/u', ' ', $normalized));

        if ($normalized === '') {
            return null;
        }

        // The greeting must OPEN the message. Unlike
        // assistantIsGreetingFlexible there is no length cap here: "Good
        // morning! What fertilizers do you have for rice?" is exactly the
        // turn this exists for, and the remainder is routed as a real
        // question after the greeting is peeled off.
        if (! preg_match(
            '/^(?:magandang\s+(?:umaga|hapon|gabi)|good\s+(?:morning|afternoon|evening|day|eve\w*)|hello|hi|hey|greetings|morning|afternoon|evening|eve\w*|how\s+are\s+you|kumusta)\b/iu',
            $normalized
        )) {
            return null;
        }

        return $this->assistantGreetingReply($message, []);
    }

    /**
     * Strip a leading greeting from a message so the remainder can be routed
     * as a real question. Returns the message unchanged when it does not
     * open with a greeting.
     */
    private function stripLeadingGreeting(string $message): string
    {
        $stripped = preg_replace(
            '/^(?:magandang\s+(?:umaga|hapon|gabi)|good\s+(?:morning|afternoon|evening|day)|hello|hi|hey|greetings|kumusta|how\s+are\s+you)\b\s*(?:po|ka)?(?:\s+(?:there|everyone|all))?[,!.?\s]*/iu',
            '',
            $message
        );

        return trim($stripped);
    }

    /**
     * True when what follows a peeled greeting is only more courtesy —
     * "good evening!", "kumusta po" — rather than a question that happens
     * to contain a greeting word ("what does the morning forecast say").
     * A greeting-only remainder is answered by the greeting itself.
     */
    private function assistantIsGreetingOnly(string $message): bool
    {
        if (! $this->assistantIsGreetingFlexible($message)) {
            return false;
        }

        return preg_match(
            '/\?|\b(?:what|when|where|why|which|who|how|do|does|did|can|could|will|would|should|is|are|was|were|show|give|list|tell|need|want)\b/iu',
            $message
        ) !== 1;
    }

    /**
     * A system-help reply built from the role's page catalog, used when a
     * greeting is followed by a help-style question.
     */
    private function assistantSystemHelpReply(string $role, string $message): string
    {
        $pages = $this->assistantPageCatalog($role);
        $links = [];

        foreach ($pages as $label => $routeName) {
            try {
                $links[] = '- [' . $label . '](' . route($routeName) . ')';
            } catch (\Throwable $e) {
                continue;
            }

            if (count($links) >= 6) {
                break;
            }
        }

        if ($role === 'store_owner') {
            $reply = "I'm the Aquilala assistant for store owners. I can help with your product listings, inventory, quotation demand trends, top products, and prices.";
        } elseif ($role === 'admin') {
            $reply = "I'm the Aquilala assistant for administrators. I can help with member management, store and product oversight, harvest records, reports, and system pages.";
        } else {
            $reply = "I'm Aquilala's assistant for your farm. I can help with product prices, fertilizer and crop advice, cost estimates, and planting guidance.";
        }

        if ($links !== []) {
            $reply .= "\nSome pages you may need:\n" . implode("\n", $links);
        }

        return $reply;
    }

    private function seasonalCropChoicePrefix(array $contextData): string
    {
        $label = (string) (($contextData['facts']['season_profile']['label'] ?? null)
            ?: app(\App\Services\FarmingKnowledgeService::class)->seasonProfile()['label'] ?? '');

        return $label !== ''
            ? 'This month in Bulan it is **' . $label . '**, which suits several crops. '
            : '';
    }

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

    /*
    |--------------------------------------------------------------------------
    | Calendar event creation (human-confirmed, low-AI)
    |--------------------------------------------------------------------------
    */

    private function calendarDraftKey(User $user): string
    {
        return 'aquilala:calendar_draft:' . $user->id;
    }

    private function calendarDraft(User $user): ?array
    {
        $draft = cache()->get($this->calendarDraftKey($user));

        return is_array($draft) ? $draft : null;
    }

    private function setCalendarDraft(User $user, array $draft): void
    {
        cache()->put($this->calendarDraftKey($user), $draft, now()->addMinutes(30));
    }

    private function clearCalendarDraft(User $user): void
    {
        cache()->forget($this->calendarDraftKey($user));
    }

    private function handleCalendarAction(string $message, User $user): ?array
    {
        if ($user->role !== 'admin') {
            if (preg_match('/\b(schedule|calendar|event|meeting)\b/iu', $message) === 1
                && preg_match('/\b(create|add|make|schedule|book)\b/iu', $message) === 1) {
                return $this->calendarResponse(
                    'Only administrators can create calendar events. You can ask an administrator to schedule it for you.'
                );
            }

            return null;
        }

        $pending = $this->calendarDraft($user);
        $trimmed = trim($message);

        // Confirm a pending draft.
        if ($pending !== null
            && preg_match('/^(?:yes|y|confirm|go\s+ahead|ok(?:ay)?|create\s+it|proceed|do\s+it)[!?.\s]*$/iu', $trimmed) === 1) {
            return $this->createCalendarEventFromDraft($user, $pending);
        }

        // Cancel a pending draft.
        if ($pending !== null
            && preg_match('/^(?:no|n|cancel|stop|abort|nevermind|never\s+mind)[!?.\s]*$/iu', $trimmed) === 1) {
            $this->clearCalendarDraft($user);

            return $this->calendarResponse('Cancelled. No event was created.');
        }

        // New scheduling request.
        if ($pending === null && $this->isCalendarCreateIntent($trimmed)) {
            $draft = [
                'step' => 'collect',
                'data' => [
                    'title'       => null,
                    'event_type'  => 'meeting',
                    'event_date'  => null,
                    'start_time'  => null,
                    'end_time'    => null,
                    'location'    => null,
                    'description' => null,
                ],
                'notes' => [],
            ];

            $this->updateCalendarDraft($draft, $trimmed);

            // If no title was provided, suggest one from the available clues
            // instead of asking the user to invent one.
            if (empty($draft['data']['title'])) {
                $draft['data']['title'] = $this->suggestCalendarTitleWithAi($draft, '');
            }

            // If the user explicitly asked for help, regenerate from the
            // clues gathered before the help request.
            if ($this->isCalendarTitleHelp($trimmed)) {
                $suggestDraft = $draft;

                if (! empty($suggestDraft['notes']) && is_array($suggestDraft['notes'])) {
                    array_pop($suggestDraft['notes']);
                }

                $draft['data']['title'] = $this->suggestCalendarTitleWithAi($suggestDraft, '');
            }

            $this->setCalendarDraft($user, $draft);

            return $this->calendarNextReply($draft, $user);
        }

        // Continue an existing draft.
        if ($pending !== null) {
            // Let an explicit title-help or new scheduling request be handled
            // by the dedicated branches below.

            // A greeting or a general system question is not a reply to the
            // pending event draft: clear it and let the normal conversation
            // handle the message.
            if ($this->assistantIsGreetingFlexible($message)
                || $this->assistantIsSystemHelp($message)
                || (! $this->isCalendarTitleHelp($trimmed)
                    && preg_match('/^(what|how|where|which|show|open|who|list|display|find|give|tell)\\b/i', trim($trimmed)) === 1)
                || (! $this->isCalendarTitleHelp($trimmed)
                    && preg_match('/\\?/', $trimmed) === 1
                    && preg_match('/\\b\\d{4}-\\d{2}-\\d{2}\\b|\\b\\d{1,2}:\\d{2}\\b|\\btoday\\b|\\btomorrow\\b/i', $trimmed) !== 1)) {
                $this->clearCalendarDraft($user);

                return null;
            }

            // If the user starts a brand-new scheduling request, reset.
            if ($this->isCalendarCreateIntent($trimmed) && str_contains(mb_strtolower($trimmed), 'schedule a new')) {
                $this->clearCalendarDraft($user);

                return $this->handleCalendarAction($message, $user);
            }

            $draft = $pending;
            $this->updateCalendarDraft($draft, $trimmed);

            // Always suggest a title when one is missing, so the user is never
            // prompted to invent one.
            if (empty($draft['data']['title'])) {
                $draft['data']['title'] = $this->suggestCalendarTitleWithAi($draft, '');
            }

            // Regenerate the title when the user explicitly asks to change it.
            if ($this->isCalendarTitleChangeIntent($trimmed)) {
                $draft['data']['title'] = $this->suggestCalendarTitleWithAi($draft, $trimmed);

                if (empty($draft['data']['title'])) {
                    $draft['data']['title'] = $this->suggestCalendarTitle($draft, $trimmed);
                }
            }

            // If the user explicitly asked for help, regenerate from the clues
            // gathered before the help request.
            if ($this->isCalendarTitleHelp($trimmed)) {
                $suggestDraft = $draft;

                if (! empty($suggestDraft['notes']) && is_array($suggestDraft['notes'])) {
                    array_pop($suggestDraft['notes']);
                }

                $draft['data']['title'] = $this->suggestCalendarTitleWithAi($suggestDraft, '');
            }

            $this->setCalendarDraft($user, $draft);

            return $this->calendarNextReply($draft, $user);
        }

        return null;
    }

    private function isCalendarCreateIntent(string $message): bool
    {
        $lower = mb_strtolower(trim($message));

        if (preg_match('/\b(?:schedule|book|create|add|make|set\s*up|arrange|gumawa|mag-sched|plan)\b/iu', $lower) === 1
            && preg_match('/\b(?:event|meeting|seminar|calendar|gathering|activity)\b/iu', $lower) === 1) {
            return true;
        }

        if (preg_match('/\b(?:gumawa|magawa|gawan|gawin)\b.*\b(?:meeting|event|seminar)\b/iu', $lower) === 1) {
            return true;
        }

        if (preg_match('/\b(?:meeting|event|seminar|gathering)\b.*\b(?:bukas|tomorrow|today|ngayon)\b/iu', $lower) === 1) {
            return true;
        }

        if (preg_match('/\b(?:meeting|event|seminar)\b/iu', $lower) === 1
            && preg_match('/\d/iu', $lower) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Best-effort, deterministic extraction from a free-text turn.
     * Missing fields are asked for one at a time, so no AI calls are made.
     *
     * @param  array<string, mixed>  $draft
     */
    private function updateCalendarDraft(array &$draft, string $message): void
    {
        if (! isset($draft['notes']) || ! is_array($draft['notes'])) {
            $draft['notes'] = [];
        }

        $trimmedMessage = trim($message);

        if ($trimmedMessage !== '' && ! in_array($trimmedMessage, $draft['notes'], true)) {
            $draft['notes'][] = $trimmedMessage;
        }

        $data = &$draft['data'];
        $lower = mb_strtolower(trim($message));

        // Event type detection.
        foreach ([
            'water'   => AssociationEvent::TYPE_WATER_SCHEDULE,
            'watering'=> AssociationEvent::TYPE_WATER_SCHEDULE,
            'input'   => AssociationEvent::TYPE_FREE_INPUT,
            'free input' => AssociationEvent::TYPE_FREE_INPUT,
            'meeting' => AssociationEvent::TYPE_MEETING,
            'seminar' => AssociationEvent::TYPE_MEETING,
            'training'=> AssociationEvent::TYPE_MEETING,
        ] as $needle => $type) {
            if (str_contains($lower, $needle)) {
                $data['event_type'] = $type;
                break;
            }
        }

        // Date: YYYY-MM-DD (allow a natural "today"/"tomorrow" too).
        // Accept common misspellings ("tommorow", "tomorow") and Taglish
        // ("bukas", "ngayon") as well.
        if (empty($data['event_date'])) {
            if (preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $message, $m) === 1) {
                $data['event_date'] = $m[1];
            } elseif (str_contains($lower, 'today') || str_contains($lower, 'ngayon')) {
                $data['event_date'] = now()->toDateString();
            } elseif (str_contains($lower, 'tomorrow')
                || str_contains($lower, 'tommorow')
                || str_contains($lower, 'tomorow')
                || str_contains($lower, 'bukas')) {
                $data['event_date'] = now()->addDay()->toDateString();
            }
        }

        // Times: first HH:MM becomes start, second becomes end.
        preg_match_all('/\b(\d{1,2}:\d{2})\b/', $message, $timeMatches);

        // Also accept "8am", "8 am", "8pm" style inputs.
        preg_match_all('/\b(\d{1,2})\s*(am|pm)\b/iu', $message, $ampmMatches, PREG_SET_ORDER);

        $ampmTimes = [];

        foreach ($ampmMatches as $match) {
            $hour = (int) $match[1];
            $meridian = mb_strtolower($match[2]);

            if ($meridian === 'pm' && $hour < 12) {
                $hour += 12;
            }

            if ($meridian === 'am' && $hour === 12) {
                $hour = 0;
            }

            $ampmTimes[] = sprintf('%02d:00', $hour);
        }

        if (empty($timeMatches[1]) && $ampmTimes !== []) {
            $timeMatches[1] = $ampmTimes;
        }

        if (! empty($timeMatches[1])) {
            if (empty($data['start_time'])) {
                $data['start_time'] = $timeMatches[1][0];
            }
            if (empty($data['end_time']) && isset($timeMatches[1][1])) {
                $data['end_time'] = $timeMatches[1][1];
            }
        }

        // Location: "at ..." (taken to end of line).
        if (empty($data['location']) && preg_match('/\bat\s+([A-Za-z].+)$/iu', $message, $m) === 1) {
            $clean = trim($m[1]);
            if ($clean !== '') {
                $data['location'] = mb_substr($clean, 0, 255);
            }
        }

        // Title: prefer an explicit "title: ..." else use the first text turn
        // when nothing else has been captured.
        if (empty($data['title'])) {
            if (preg_match('/\btitle\s*[:=-]\s*(.+)$/iu', $message, $m) === 1) {
                $data['title'] = trim($m[1]);
            } elseif ($data['event_date'] === null && $data['start_time'] === null) {
                // Treat a non-data question/answer as the title candidate.
                $candidate = trim($message);
                // Drop a leading intent phrase so it doesn't become the title.
                $candidate = preg_replace(
                    '/^(?:please\s+)?(?:can\s+you\s+)?(?:schedule|create|add|book|set\s+up)\s+(?:a\s+|an\s+)?(?:meeting|event|seminar|gathering)?\s*/iu',
                    '',
                    $candidate
                );
                $candidate = trim((string) $candidate);
                $candidate = preg_replace('/[?!.]+$/u', '', $candidate);
                $candidate = trim((string) $candidate);

                if ($candidate !== '' && mb_strlen($candidate) <= 120
                    && ! preg_match('/^[?!.]+$/u', $candidate)) {
                    $data['title'] = $candidate;
                }
            }
        }

        // If a title candidate still contains a date, trim it back to the
        // human-readable part before the date token.
        if (! empty($data['title']) && preg_match('/^(.+?)\s+\d{4}-\d{2}-\d{2}/', $data['title'], $m) === 1) {
            $data['title'] = trim($m[1]);
        }

        // Any leftover free text (not date/time/location) can become a
        // short description, only if the user explicitly adds it.
        if (empty($data['description'])
            && preg_match('/\b(?:about|description|notes?)\s*[:=-]\s*(.+)$/iu', $message, $m) === 1) {
            $data['description'] = trim($m[1]);
        }
    }

    private function calendarNextReply(array $draft, ?User $user = null): array
    {
        $data = $draft['data'];

        $missing = [];
        if (empty($data['title'])) {
            $missing[] = 'title';
        }
        if (empty($data['event_date'])) {
            $missing[] = 'date (YYYY-MM-DD)';
        }

        if ($missing !== []) {
            $ask = 'I can schedule that for you. Please provide the missing: ' . implode(', ', $missing) . '.';

            if (empty($data['title'])) {
                $ask .= "\n\nWhat should the event be called?";
            } elseif (empty($data['event_date'])) {
                $ask .= "\n\nWhat date should it be on? (use YYYY-MM-DD; you may also say today or tomorrow)";
            }

            return $this->calendarResponse($ask);
        }

        // Present the plan and wait for explicit confirmation.
        $draft['step'] = 'confirm';

        if ($user !== null) {
            $this->setCalendarDraft($user, $draft);
        }

        return $this->calendarPlanResponse($draft);
    }

    private function calendarPlanResponse(array $draft): array
    {
        $data = $draft['data'];

        $lines = [
            'Here is what I plan to add to the calendar:',
            '',
            '- **Title:** ' . $data['title'],
            '- **Type:** ' . (AssociationEvent::TYPES[$data['event_type']] ?? 'Meeting / Seminar'),
            '- **Date:** ' . $data['event_date'],
        ];

        if (! empty($data['start_time'])) {
            $lines[] = '- **Start time:** ' . $data['start_time']
                . (empty($data['end_time']) ? '' : '  (end ' . $data['end_time'] . ')');
        }

        if (! empty($data['location'])) {
            $lines[] = '- **Location:** ' . $data['location'];
        }

        if (! empty($data['description'])) {
            $lines[] = '- **Description:** ' . $data['description'];
        }

        $lines[] = '';
        $lines[] = 'This looks correct? Reply **yes** to create it, **cancel** to stop, or add more details (time, location, description) before confirming.';

        return $this->calendarResponse(implode("\n", $lines));
    }

    private function createCalendarEventFromDraft(User $user, array $draft): array
    {
        $data = $draft['data'];

        if (empty($data['title']) || empty($data['event_date'])) {
            $this->setCalendarDraft($user, $draft);

            return $this->calendarResponse(
                'I still need a title and a date. Please provide them first.'
            );
        }

        try {
            $eventDate = \Carbon\Carbon::parse($data['event_date'])->toDateString();

            if (\Carbon\Carbon::parse($eventDate)->lt(today())) {
                throw new \InvalidArgumentException('past date');
            }
        } catch (\Throwable $e) {
            $draft['data']['event_date'] = null;
            $this->setCalendarDraft($user, $draft);

            return $this->calendarResponse(
                'That date is not valid or is in the past. Please provide a valid date (YYYY-MM-DD).'
            );
        }

        try {
            $event = AssociationEvent::create([
                'title'       => $data['title'],
                'event_type'  => $data['event_type'] ?? AssociationEvent::TYPE_MEETING,
                'event_date'  => $eventDate,
                'start_time'  => $data['start_time'] ?: null,
                'end_time'    => $data['end_time'] ?: null,
                'location'    => $data['location'],
                'description' => $data['description'],
                'created_by'  => $user->id,
            ]);

            if (\Illuminate\Support\Facades\Auth::user() === null) {
                \Illuminate\Support\Facades\Auth::login($user);
            }

            \App\Services\ActivityLogger::eventCreated($event);
        } catch (\Throwable $e) {
            $this->setCalendarDraft($user, $draft);

            return $this->calendarResponse(
                'I could not create the event: ' . $e->getMessage()
            );
        }

        $this->clearCalendarDraft($user);

        return $this->calendarResponse(sprintf(
            'Created **%s** on **%s**%s. You can view or edit it on the calendar page.',
            $event->title,
            $event->event_date->format('M d, Y'),
            empty($data['start_time']) ? '' : ' at ' . $data['start_time']
        ));
    }

    private function isCalendarTitleChangeIntent(string $message): bool
    {
        $lower = mb_strtolower(trim($message));

        if (preg_match('/\b(palitan|baguhin|change|rename|make it about|make the title|gawin mong|gawin mo|instead of|instead)\b/iu', $lower) === 1) {
            return true;
        }

        if (str_contains($lower, 'tungkol sa') || str_contains($lower, 'about ')) {
            return true;
        }

        return false;
    }

    private function isCalendarTitleHelp(string $message): bool
    {
        $lower = mb_strtolower(trim($message));

        if (preg_match('/\b(help|suggest|generate|create|make|give)\b.*\b(title|name)\b/iu', $lower) === 1) {
            return true;
        }

        if (preg_match('/\b(title|name)\b.*\b(help|suggest|generate|create|make|give)\b/iu', $lower) === 1) {
            return true;
        }

        return str_contains($lower, 'help with title')
            || str_contains($lower, 'suggest title')
            || $lower === 'help'
            || $lower === 'help me';
    }

    /*
     * Best-effort title suggestion. Prefers a short LLM-generated title when
     * the model is available (one small call), then falls back to a fast
     * deterministic heuristic so scheduling still works with no AI quota.
     */
    private function suggestCalendarTitleWithAi(array $draft, string $hint = ''): string
    {
        $heuristic = $this->suggestCalendarTitle($draft, $hint);

        if (! $this->llm->available()) {
            return $heuristic;
        }

        $data = $draft['data'] ?? [];
        $notes = $draft['notes'] ?? [];
        $context = trim(implode(' ', $notes));

        if ($hint !== '') {
            if ($context !== '') {
                $context .= ' Latest change request: ' . trim($hint);
            } else {
                $context = trim($hint);
            }
        }

        if ($context === '') {
            $context = trim(($data['description'] ?? '') . ' ' . ($data['location'] ?? ''));
        }

        if ($context === '') {
            return $heuristic;
        }

        try {
            $completion = app(\App\AI\AiLlmClient::class)->completeJson([
                [
                    'role'    => 'system',
                    'content' => 'You write clear, human-friendly calendar event titles. '
                        . 'Return ONLY a JSON object: {"title": "3 to 6 words"}. '
                        . 'Focus on the event topic or purpose, not commands like "tell members", '
                        . 'not filler words, and not random phrases. '
                        . 'Example: for "schedule a meeting tomorrow at 8 to elect officers and take attendance", '
                        . 'return "Election of Officers Meeting".',
                ],
                [
                    'role'    => 'user',
                    'content' => 'Suggest an event title for this request: ' . $context,
                ],
            ], 512, 30);
        } catch (\Throwable $e) {
            return $heuristic;
        }


        $raw = is_array($completion) ? (string) ($completion['raw'] ?? '') : '';


        if ($raw === '') {
            return $heuristic;
        }

        $parsed = json_decode($raw, true);
        $title = '';

        if (is_array($parsed) && isset($parsed['title'])) {
            $title = trim((string) $parsed['title']);
        }

        if ($title === '') {
            if (preg_match('/"title"\s*:\s*"([^"]+)"/iu', $raw, $m) === 1) {
                $title = trim($m[1]);
            }
        }

        if ($title === '') {
            return $heuristic;
        }

        // Keep the title short and clean.
        $title = preg_replace('/[?!.]+$/u', '', $title);
        $title = trim((string) $title);

        if (mb_strlen($title) > 120) {
            $title = mb_substr($title, 0, 120);
        }

        return $title !== '' ? $title : $heuristic;
    }

    private function suggestCalendarTitle(array $draft, string $hint = ''): string
    {
        $data = $draft['data'] ?? [];
        $notes = $draft['notes'] ?? [];
        $raw = mb_strtolower(trim(($data['location'] ?? '') . ' ' . implode(' ', $notes) . ' ' . $hint));

        // Prefer a clear, human-readable title for common intents instead of
        // echoing the user's full sentence back as the title.
        if (str_contains($raw, 'budget')) {
            return 'Budget review meeting';
        }

        if (str_contains($raw, 'attendance')) {
            return 'Attendance meeting';
        }

        if (str_contains($raw, 'training') || str_contains($raw, 'seminar')) {
            return 'Training session';
        }

        if (str_contains($raw, 'harvest') && str_contains($raw, 'report')) {
            return 'Harvest report review';
        }

        if (str_contains($raw, 'updates') || str_contains($raw, 'update')) {
            return 'Updates meeting';
        }

        if (! empty($data['description'])) {
            $words = preg_split('/\s+/u', trim((string) $data['description']));
            $candidate = implode(' ', array_slice($words ?: [], 0, 6));

            if ($candidate !== '') {
                return ucfirst(mb_substr(trim($candidate), 0, 120));
            }
        }

        $text = trim(($data['location'] ?? '') . ' ' . implode(' ', $notes) . ' ' . $hint);

        // Drop common scheduling words and date/time tokens, leaving the
        // substantive topic of the requested event.
        $text = preg_replace(
            '/\b(schedule|create|add|book|set|up|a|an|the|event|meeting|seminar|gathering|activity|title|help|suggest|generate|create|make|give|name|today|tomorrow|at|on|for|about|my|your|our|me|with|this|can|you|the)\b/iu',
            ' ',
            $text
        );
        $text = preg_replace('/\b\d{4}-\d{2}-\d{2}\b/u', ' ', $text);
        $text = preg_replace('/\b\d{1,2}:\d{2}\b/u', ' ', $text);
        $text = preg_replace('/\b\d{1,2}\s*(?:am|pm)\b/iu', ' ', $text);
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        if ($text === '') {
            $date = $data['event_date'] ?? '';

            return $date !== '' ? 'Event on ' . $date : 'Scheduled Event';
        }

        $words = preg_split('/\s+/u', $text);
        $unique = [];
        $seen = [];

        foreach ($words ?: [] as $word) {
            $word = trim($word);

            if ($word === '') {
                continue;
            }

            $key = mb_strtolower($word);

            if (in_array($key, $seen, true)) {
                continue;
            }

            $seen[] = $key;
            $unique[] = $word;
        }

        $candidate = implode(' ', array_slice($unique, 0, 6));

        return ucfirst(mb_substr(trim($candidate), 0, 120));
    }

    private function calendarResponse(string $reply): array
    {
        $context = new AiConversationContext();

        return [
            'reply'       => $reply,
            'type'        => 'answer',
            'suggestions' => [],
            'context'     => $context->toArray(),
            'intent'      => \App\AI\AiIntent::SYSTEM_HELP,
            'source'      => 'conversation',
            'violations'  => [],
        ];
    }

    private function setCalendarDraftResolved(array $draft): void
    {
        // no-op
    }
}
