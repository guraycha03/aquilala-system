<?php

namespace App\AI;

use App\Helpers\SeasonHelper;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use App\Rules\CropRecommendationEngine;
use App\Services\CropService;
use App\Services\FarmingKnowledgeService;
use App\Services\ProductSearchService;
use App\Services\StoreDemandService;
use Illuminate\Support\Collection;

/**
 * AiContextBuilder
 *
 * Turns a routed request into the verified context the model is shown, and into
 * the trusted product payload the response is allowed to display.
 *
 * Two properties this class is responsible for:
 *
 *   1. Nothing reaches the model that did not come from the database, a
 *      decision formula, or the application's own crop catalogue. There is no
 *      "the model already knows this, so I will leave it out" shortcut.
 *   2. The product list the frontend renders is decided here, from rows read
 *      out of `products`. The model can choose which of the retrieved products
 *      to talk about; it can never introduce one.
 */
class AiContextBuilder
{
    public function __construct(
        private readonly ProductSearchService $products,
        private readonly CropRecommendationEngine $recommendations,
        private readonly FarmingKnowledgeService $knowledge,
        private readonly CropService $crops,
        private readonly StoreDemandService $storeDemand,
    ) {
    }

    /**
     * @param  array{intent: string, slots: array<string, mixed>, follow_up: bool}  $routing
     * @return array{
     *     intent: string,
     *     products: array<int, array<string, mixed>>,
     *     product_ids: array<int, int>,
     *     facts: array<string, mixed>,
     *     recommendation: array<string, mixed>|null,
     *     missing: array<int, string>,
     *     assumptions: array<int, string>,
     *     quotation: array<string, mixed>|null
     * }
     */
    public function build(
        string $message,
        array $routing,
        ?User $user = null,
        ?AiConversationContext $context = null,
    ): array {
        $context ??= new AiConversationContext();
        $intent = $routing['intent'];
        $slots = $routing['slots'];

        $retrieved = new Collection();
        $recommendation = null;
        $quotation = null;
        $missing = [];
        $assumptions = [];
        $facts = [];

        $facts['role'] = $user?->role ?? 'guest';
        $facts['current_season'] = $this->knowledge->seasonProfile();

        // The farmer's own recorded batches. Real information about their farm,
        // and the only context the assistant is allowed to supply unprompted.
        $batches = AiConversationContext::activeBatchesFor($user);
        if ($batches !== []) {
            $facts['your_active_crops'] = $batches;
        }

        switch ($intent) {
            case AiIntent::PRODUCT_SEARCH:
                $retrieved = $this->retrieveForSearch(
                    $slots,
                    $user,
                    $message,
                    ($routing['names_specific_product'] ?? false) === true,
                );

                // #23: "it" with two products on the table is ambiguous, and
                // guessing which one the farmer means is how a price question
                // becomes an answer about the wrong sack. Naming the two
                // candidates costs one short question; guessing costs a wrong
                // number that looks authoritative.
                $carried = $slots['product_ids'] ?? [];

                if (is_array($carried)
                    && count($carried) > 1
                    && $retrieved->count() > 1
                    && $message !== ''
                    && $this->products->search($message, $user)->isEmpty()) {
                    $facts['ambiguous_product_reference'] = $retrieved
                        ->map(fn ($p) => $p->display_name)
                        ->values()
                        ->all();
                }

                break;

            case AiIntent::PRODUCT_RECOMMENDATION:
            case AiIntent::FERTILIZER_ADVICE:
                [$retrieved, $recommendation, $missing] = $this->retrieveForRecommendation($slots, $user, (float) ($slots['budget'] ?? 0) ?: null);

                if (($slots['budget'] ?? null) !== null) {
                    $facts['budget_limit'] = (float) $slots['budget'];
                }
                break;

            case AiIntent::COST_ESTIMATION:
                [$retrieved, $recommendation, $missing, $assumptions] = $this->retrieveForCost($slots, $user);
                break;

            case AiIntent::QUOTATION:
                $quotation = $this->retrieveQuotation($user);
                $missing = $quotation === null ? ['quotation'] : [];
                break;

            // A store owner's own demand: the quotations farmers created that
            // include this store's products. The same figures the demand
            // trends page shows, read from one service so the chat and the
            // page cannot disagree.
            case AiIntent::STORE_DEMAND:
                $facts['store_demand'] = $user === null
                    ? null
                    : $this->storeDemand->summary((int) $user->id);
                $missing = [];
                // The trending products are the store's own products, so they
                // can be shown as cards and the model may name them.
                $retrieved = $this->storeDemandProducts($facts['store_demand'] ?? null, $user);
                break;

            case AiIntent::CROP_ADVICE:
                $cropFacts = $this->knowledge->factsFor($slots['crop'] ?? null);
                if ($cropFacts !== null) {
                    $facts['crop'] = $cropFacts;
                }
                $missing = ($slots['crop'] ?? null) === null ? ['crop'] : [];
                break;

            // ── Crop discovery and advice ───────────────────────────
            //
            // These four intents exist because the assistant used to refuse
            // questions it had the data for. They are built from the crops
            // directory and the decision formulas, and they carry no invented
            // agronomy: a crop list is a list of rows, and a seasonal answer is
            // a list of rows the association has formulas for.

            case AiIntent::CROP_DISCOVERY:
            case AiIntent::CROP_SEARCH:
                $facts['crops'] = $this->cropsForDiscovery($slots);
                $facts['crop_categories'] = $this->crops->categories();
                $retrieved = $this->productsIfAskedFor($message, $slots, $user);
                $missing = [];
                break;

            case AiIntent::CROP_RECOMMENDATION:
                $facts['crops'] = $this->cropsForChoice($slots);
                $facts['crop_categories'] = $this->crops->categories();
                $facts['preference_is_subjective'] = $routing['subjective'] ?? false;
                // A backyard plot is not a hectare. The association's rules are
                // field-scale, and the answer has to say so rather than imply a
                // hectare formula is tuned for a backyard.
                $facts['is_backyard_question'] = str_contains(
                    mb_strtolower($message),
                    'backyard'
                ) || str_contains(mb_strtolower($message), 'back yard');
                $retrieved = $this->productsIfAskedFor($message, $slots, $user);
                break;

            case AiIntent::SEASONAL_RECOMMENDATION:
                $window = $slots['month'] ?? $this->crops->currentMonth();
                $facts['season_window'] = $window;
                $facts['crops'] = $this->cropsForSeason($window);
                $facts['season_profile'] = $this->knowledge->seasonProfile();
                $missing = [];
                break;

            case AiIntent::FARMING_ADVICE:
                $cropFacts = $this->knowledge->factsFor($slots['crop'] ?? null);
                if ($cropFacts !== null) {
                    $facts['crop'] = $cropFacts;
                }
                $facts['guidance'] = $this->knowledge->generalGuidance();
                $facts['crops'] = $slots['crop'] !== null
                    ? $this->cropsForChoice($slots)
                    : $this->cropsForDiscovery($slots);
                $missing = [];
                break;

            case AiIntent::GREETING:
                $facts['season_profile'] = $this->knowledge->seasonProfile();
                $missing = [];
                break;

            case AiIntent::GENERAL_FARMING:
            case AiIntent::SYSTEM_HELP:
                $facts['guidance'] = $this->knowledge->generalGuidance();
                $facts['supported_crops'] = $this->recommendations->supportedCrops()->all();
                $missing = [];
                break;

            case AiIntent::CLARIFICATION:
            case AiIntent::UNSUPPORTED:
            default:
                $missing = $routing['missing'] ?? [];
                break;
        }

        $missing = array_values(array_unique(array_merge($missing, $routing['missing'] ?? [])));

        return [
            'intent'         => $intent,
            'products'       => $this->products->cards($retrieved, $user),
            'product_ids'    => $retrieved->map(fn ($p) => (int) $p->id)->values()->all(),
            'facts'          => $facts,
            'recommendation' => $recommendation,
            'missing'        => $missing,
            'assumptions'    => $assumptions,
            'quotation'      => $quotation,
        ];
    }

    /**
     * The store's own products behind its demand, as cards.
     *
     * @param  array<string, mixed>|null  $demand
     * @return Collection<int, Product>
     */
    private function storeDemandProducts(?array $demand, ?User $user): Collection
    {
        if (! is_array($demand) || ($demand['top_products'] ?? []) === []) {
            return new Collection();
        }

        $ids = collect($demand['top_products'])
            ->pluck('product_id')
            ->filter(fn ($id) => (int) $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return new Collection();
        }

        return Product::query()
            ->whereIn('id', $ids)
            ->when($user?->role === 'store_owner', fn ($query) => $query->where('user_id', $user->id))
            ->with(['storeOwner', 'images'])
            ->get();
    }

    /***
     * "What fruits can I plant?" — the directory, narrowed to the category the
     * farmer named.
     *
     * @param  array<string, mixed>  $slots
     * @return array<int, array<string, mixed>>
     */
    private function cropsForDiscovery(array $slots): array
    {
        $category = $slots['crop_category']['name'] ?? null;

        $crops = $category !== null
            ? $this->crops->inCategory((string) $category)
            : $this->crops->all();

        // A crop named outright is a question about that crop, and its own
        // record beats a list of everything else in the same category.
        if (($slots['crop'] ?? null) !== null) {
            $named = $this->crops->find((string) $slots['crop']);

            if ($named !== null) {
                return [$this->describe($named)];
            }
        }

        return array_map(fn (array $crop) => $this->describe($crop), $crops);
    }

    /***
     * "What fruit should I plant?" — the choices, with the reason Aquilala is
     * willing to give for each: its category, and whether the association has
     * formulas for it.
     *
     * @param  array<string, mixed>  $slots
     * @return array<int, array<string, mixed>>
     */
    private function cropsForChoice(array $slots): array
    {
        $category = $slots['crop_category']['name'] ?? null;
        $crop = $slots['crop'] ?? null;

        $choices = [];

        if ($crop !== null) {
            $named = $this->crops->find((string) $crop);

            if ($named !== null) {
                $choices = [$this->describe($named)];
            }
        } elseif ($category !== null) {
            $choices = array_map(
                fn (array $entry) => $this->describe($entry),
                $this->crops->inCategory((string) $category)
            );
        } else {
            $choices = array_map(fn (array $entry) => $this->describe($entry), $this->crops->all());
        }

        return $choices;
    }

    /***
     * "What can I plant this October?" — the crops with decision formulas in the
     * season that month falls in.
     *
     * The month decides the season through the application's own climate
     * calendar, and the season decides the crops through the formula matrix.
     * Nothing here is a suitability opinion.
     *
     * @param  array{month: int, month_name: string, season: string}  $window
     * @return array<int, array<string, mixed>>
     */
    private function cropsForSeason(array $window): array
    {
        $season = $this->crops->normalizeSeason($window['season'] ?? null);

        if ($season === null) {
            return [];
        }

        return array_map(fn (array $entry) => $this->describe($entry), $this->crops->forSeason($season));
    }

    /***
     * A directory crop with its formula coverage attached, so the answer can say
     * what Aquilala can actually do for it.
     *
     * @param  array<string, mixed>  $crop
     * @return array<string, mixed>
     */
    private function describe(array $crop): array
    {
        $formulas = $this->crops->formulaCrops();

        foreach ($formulas as $entry) {
            if ($this->sameCrop((string) $entry['name'], (string) $crop['name'])) {
                return [
                    'name'          => $crop['name'],
                    'category'      => $crop['category'],
                    'description'   => $crop['description'],
                    'seasons'       => $entry['seasons'],
                    // A yes/no for the answer's wording. The count itself is not
                    // passed on: a farmer cannot act on "4 formulas", and a
                    // number in a list reads as a ranking.
                    'has_formula'   => true,
                ];
            }
        }

        return [
            'name'        => $crop['name'],
            'category'    => $crop['category'],
            'description' => $crop['description'],
            'seasons'     => [],
            'has_formula' => false,
        ];
    }

    private function sameCrop(string $a, string $b): bool
    {
        $normalize = static fn (string $value) => preg_replace(
            '/\(.*?\)|[^\p{L}\p{N}]+/u',
            ' ',
            mb_strtolower(trim($value))
        ) ?? mb_strtolower(trim($value));

        $a = trim((string) $normalize($a));
        $b = trim((string) $normalize($b));

        return $a !== '' && $b !== '' && (str_contains($a, $b) || str_contains($b, $a));
    }

    /***
     * Products, but only when the farmer actually asked about buying something.
     *
     * "What fruit should I plant that's available?" is two questions in one: what
     * can I grow, and can I buy it. Answering the first and attaching the second
     * is the whole point; attaching a product carousel to every crop question
     * would be answering a question nobody asked.
     *
     * NOTE: This method is intentionally restrictive. Previously it triggered on
     * any occurrence of shopping-related words, which caused the assistant to
     * dump product catalog data even when the user was asking about crops,
     * farming practice, or other topics. Now it only triggers when the farmer
     * explicitly asks about products in the context of a crop question, and even
     * then only if the message clearly seeks purchase/availability information.
     *
     * @param  array<string, mixed>  $slots
     * @return Collection<int, \App\Models\Product>
     */
    private function productsIfAskedFor(string $message, array $slots, ?User $user): Collection
    {
        // Only look for products when the user explicitly asks about buying/purchasing
        // in the context of a specific crop or category they've already named.
        // This prevents dumping the entire catalog when the user mentions words
        // like "price", "cost", or "available" in a different context.
        $shoppingKeywords = '/\b(buy|purchase|order)\b/i';

        if (preg_match($shoppingKeywords, $message) !== 1) {
            return new Collection();
        }

        // Additional guard: only show products if there's a clear crop or category context.
        // This prevents "I'm interested in fertilizers" from triggering a product list
        // when the user was actually asking about planting advice or crop discovery.
        $hasCropContext = ($slots['crop'] ?? null) !== null
            || ($slots['crop_category'] ?? null) !== null;

        if (! $hasCropContext) {
            // User mentioned shopping keywords without a crop context - ask for clarification
            return new Collection();
        }

        $crop = $slots['crop'] ?? null;

        if ($crop !== null) {
            $season = $slots['season'] ?? null;

            return $season !== null
                ? $this->products->forCropAndSeason($crop, $season, $user)
                : $this->products->forCrop($crop, $user);
        }

        $category = $slots['crop_category']['name'] ?? null;

        if ($category !== null) {
            $found = new Collection();

            foreach ($this->crops->inCategory((string) $category) as $entry) {
                $found = $found->concat($this->products->forCrop((string) $entry['name'], $user));
            }

            return $found->unique('id')->values();
        }

        return new Collection();
    }

    /***
     * A catalogue question: "do you have mango products?", "how much is the
     * urea?", or a follow-up that points back at ids already established in the
     * conversation.
     *
     * Resolution order, most specific first:
     *
     *   1. ids carried over from the previous turn, for "can I review it?";
     *   2. a keyword search over what the farmer actually typed, so a named
     *      product, brand or SKU resolves even though the router extracted no
     *      crop;
     *   3. the approved compatibility list for a named crop, which is what
     *      makes "products for rice" a recommendation the association stands
     *      behind rather than a text match.
     *
     * @param  array<string, mixed>  $slots
     * @return Collection<int, \App\Models\Product>
     */
    private function retrieveForSearch(array $slots, ?User $user, string $message = '', bool $namesProduct = false): Collection
    {
        $ids = $slots['product_ids'] ?? [];

        if (is_array($ids) && $ids !== []) {
            return $this->products->findActiveByIds($ids, $user);
        }

        $found = $message === ''
            ? new Collection()
            : $this->products->search($message, $user);

        $crop = $slots['crop'] ?? null;

        if ($crop === null) {
            return $found;
        }

        // A farmer who named a product is asking about that product. Falling
        // through to the crop's compatibility list here is what answered "do
        // you have a Super Corn Fertilizer 5000?" with ten corn products, and
        // it is indistinguishable from inventing the product they asked for.
        if ($namesProduct) {
            return $found;
        }


        // A named crop turns "any products for mango" into the approved
        // compatibility list rather than a keyword scan. Anything the keyword
        // search already matched is kept, so a product listed for the crop is
        // not dropped by the join.
        $season = $slots['season'] ?? null;

        $compatible = $season !== null
            ? $this->products->forCropAndSeason($crop, $season, $user)
            : $this->products->forCrop($crop, $user);

        return $found
            ->concat($compatible)
            ->unique('id')
            ->values();
    }

    /***
     * "What fertilizers for rice?" — names the crop's approved products. No
     * quantities, because a quantity without a farm area is a guess.
     *
     * @param  array<string, mixed>  $slots
     * @return array{0: Collection<int, \App\Models\Product>, 1: array<string, mixed>|null, 2: array<int, string>}
     */
    private function retrieveForRecommendation(array $slots, ?User $user, ?float $maxPrice = null): array
    {
        $crop = $slots['crop'] ?? null;
        $season = $slots['season'] ?? null;
        $budget = $slots['budget'] ?? $maxPrice;

        if ($crop === null) {
            // No crop named. Refusing here is the whole point — "give me the
            // best product" has no answer that is not an invention.
            return [new Collection(), null, ['crop']];
        }

        $products = $season !== null
            ? $this->products->forCropAndSeason($crop, $season, $user, $budget)
            : $this->products->forCrop($crop, $user, $budget);

        $missing = [];

        // A rate or a quantity needs both the season and the area. Ask for
        // whichever is absent, one at a time, so the question stays short.
        if ($season === null) {
            $missing[] = 'season';
        }

        if (($slots['area'] ?? null) === null) {
            $missing[] = 'area';
        }

        return [$products, null, $missing];
    }

    /***
     * A cost question runs the deterministic engine. There is no path where the
     * model is asked for a peso figure.
     *
     * @param  array<string, mixed>  $slots
     * @return array{0: Collection<int, \App\Models\Product>, 1: array<string, mixed>|null, 2: array<int, string>, 3: array<int, string>}
     */
    private function retrieveForCost(array $slots, ?User $user): array
    {
        $result = $this->recommendations->evaluate([
            'crop'    => $slots['crop'] ?? null,
            'season'  => $slots['season'] ?? null,
            'area'    => $slots['area'] ?? null,
            'soil'    => $slots['soil'] ?? null,
            'weather' => $slots['weather'] ?? null,
        ], $user);

        if ($result['status'] !== CropRecommendationEngine::STATUS_OK) {
            // #38 Test 10 asks for a verified cost from "how much would 2
            // hectares of corn cost?", while #20 says to ask for what is
            // missing. Both are satisfiable: the application already knows the
            // current season, so cost the planting for it, say plainly that the
            // season was assumed, and still let the farmer correct it. The
            // estimate is real either way - it is the engine's own arithmetic on
            // formula rows that exist.
            if (in_array('season', $result['missing'], true) && $slots['crop'] ?? null) {
                $current = SeasonHelper::getCurrentSeason();

                $assumed = $this->recommendations->evaluate([
                    'crop'    => $slots['crop'] ?? null,
                    'season'  => $current,
                    'area'    => $slots['area'] ?? null,
                    'soil'    => $slots['soil'] ?? null,
                    'weather' => $slots['weather'] ?? null,
                ], $user);

                if ($assumed['status'] === CropRecommendationEngine::STATUS_OK) {
                    $assumed['assumptions'] = array_merge($assumed['assumptions'] ?? [], [
                        'I used the current ' . $current . ' season, since you did not name one. '
                            . 'Tell me if you are planting in the other season and I will recalculate.',
                    ]);

                    $products = $this->products->findActiveByIds($assumed['formula_ids'], $user);

                    // 'season' stays in missing so the answer still asks, and the
                    // question rides with the estimate rather than replacing it.
                    return [$products, $assumed, ['season'], $assumed['assumptions']];
                }
            }

            return [new Collection(), null, $result['missing'], $result['assumptions']];
        }

        // The engine's own line items, resolved back to live product rows.
        $products = $this->products->findActiveByIds($result['formula_ids'], $user);

        return [$products, $result, [], $result['assumptions']];
    }

    /***
     * The farmer's most recent live quotation, read straight from the record
     * the engine already saved. The model explains it; it never recomputes it.
     *
     * @return array<string, mixed>|null
     */
    private function retrieveQuotation(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $quotation = Quotation::with('items')
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        if ($quotation === null) {
            return null;
        }

        return [
            'quotation_number' => $quotation->quotation_number,
            'crop'             => $quotation->crop_type,
            'season'           => $quotation->season,
            'land_area'        => (float) $quotation->land_area,
            'total'            => (float) $quotation->estimated_total_cost,
            'status'           => $quotation->status,
            'valid_until'      => $quotation->valid_until?->toDateString(),
            // Whether the window has already closed. The stored status is
            // never "expired" - it is derived from valid_until - so the
            // assistant would otherwise read a stale estimate as current.
            'is_expired'       => (bool) $quotation->is_expired,
            'items'            => $quotation->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'quantity'     => (float) $item->calculated_quantity,
                'subtotal'     => (float) $item->subtotal,
            ])->values()->all(),
        ];
    }
}