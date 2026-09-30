<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * ProductSearchService
 *
 * The single read path the AI Assistant is allowed to use for product data.
 *
 * Two rules make this the only place that matters:
 *
 *  1. Prices, stock, availability and names are read from the database, never
 *     from model output. The assistant cannot widen this surface because it
 *     only ever receives what this service returns.
 *  2. Product URLs are generated here with the application's route system.
 *     The LLM is never asked for, and is never allowed to supply, a link.
 *
 * Store owners are scoped to their own inventory, matching the catalogue
 * surfaces they already see.
 */
class ProductSearchService
{
    /**
     * How many product cards one assistant turn may render. Enough to answer
     * "what do you have for rice", small enough that the frontend carousel and
     * the prompt payload both stay readable.
     */
    public const MAX_RESULTS = 10;

    /**
     * Candidates pulled from the database before stores are interleaved.
     * The cap keeps a broad keyword from dragging a whole catalogue into
     * memory just to pick ten cards out of it.
     */
    private const CANDIDATE_LIMIT = 60;

    /**
     * Free-text search across the fields a merchant or farmer would actually
     * type. Returns active products only, so a discontinued line can never be
     * recommended.
     *
     * @return Collection<int, Product>
     */
    public function search(string $query, ?User $user = null, int $limit = self::MAX_RESULTS): Collection
    {
        $keywords = $this->keywords($query);

        if ($keywords === []) {
            return collect();
        }

        $candidates = $this->baseQuery($user)
            ->where(function ($outer) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $like = '%' . $this->escapeLike($keyword) . '%';

                    $outer->orWhere(function ($inner) use ($like) {
                        $inner->where('name', 'like', $like)
                            ->orWhere('brand', 'like', $like)
                            ->orWhere('product_type', 'like', $like)
                            ->orWhere('description', 'like', $like)
                            ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like));
                    });
                }
            })
            ->orderByDesc('created_at')
            ->limit(self::CANDIDATE_LIMIT)
            ->get();

        return $this->spreadAcrossStores(
            $this->keepOnlyRelevant($candidates, $keywords),
            $limit
        );
    }

    /**
     * Drop the candidates that matched on a single word of a longer question.
     *
     * The SQL above is an OR across every keyword, which on its own means one
     * common word is enough: "do you have a Super Corn Fertilizer 5000" came
     * back with ten products, every one of them matched only on the word
     * "corn" or "fertilizer", and the farmer was handed a shelf instead of an
     * answer about the thing they asked for. That is the shape of a fabricated
     * product even when no name was invented.
     *
     * A candidate therefore has to account for the *distinctive* words of the
     * question. Words like "fertilizer" or "seeds" name the catalogue rather
     * than the product, and every row in the category has them, so requiring
     * them would make the rule meaningless; the words that identify one product
     * are the ones that have to match.
     *
     * @param  Collection<int, Product>  $candidates
     * @param  array<int, string>  $keywords
     * @return Collection<int, Product>
     */
    private function keepOnlyRelevant(Collection $candidates, array $keywords): Collection
    {
        // Nouns that describe the shop's shelves instead of a product on one of
        // them. Matching on these tells you nothing about which product.
        $generic = [
            'fertilizer', 'fertilizers', 'fertiliser', 'fertilisers',
            'seed', 'seeds', 'input', 'inputs', 'brand', 'brands', 'item', 'items',
            'crop', 'crops', 'farm', 'agricultural', 'agriculture',
        ];

        $distinctive = array_values(array_filter(
            $keywords,
            static fn (string $keyword): bool => ! in_array($keyword, $generic, true)
        ));

        $required = $distinctive === [] ? $keywords : $distinctive;

        return $candidates
            ->filter(function (Product $product) use ($required): bool {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    $product->name,
                    $product->brand,
                    $product->product_type,
                    $product->description,
                    $product->category,
                ])));

                foreach ($required as $keyword) {
                    if ($keyword === '' || ! str_contains($haystack, $keyword)) {
                        return false;
                    }
                }

                return true;
            })
            ->values();
    }

    /**
     * Resolve specific product ids back to live rows.
     *
     * Ids that do not exist, are inactive, or belong to another store owner's
     * catalogue are simply absent from the result, which is what makes this
     * safe to hand ids that came from anywhere — including the model.
     *
     * @param  array<int, mixed>  $ids
     * @return Collection<int, Product>
     */
    public function findActiveByIds(array $ids, ?User $user = null): Collection
    {
        $ids = $this->cleanIds($ids);

        if ($ids === []) {
            return collect();
        }

        $products = $this->baseQuery($user)->whereIn('id', $ids)->get();

        // Preserve the caller's ordering so "the third result they asked
        // about" stays the third card.
        return $products->sortBy(fn (Product $p) => array_search($p->id, $ids, true))->values();
    }

    /**
     * Products the association has an approved formula for, for one crop in
     * one season. This is the authoritative crop/product compatibility gate —
     * the same join QuotationController::farmerProducts() uses.
     *
     * @return Collection<int, Product>
     */
    public function forCropAndSeason(string $crop, string $season, ?User $user = null): Collection
    {
        $products = $this->formulaQuery($user)
            ->whereRaw('LOWER(decision_formulas.crop_type) = ?', [mb_strtolower(trim($crop))])
            ->where('decision_formulas.season', $season)
            ->distinct()
            ->get();

        return $this->spreadAcrossStores($products, self::MAX_RESULTS);
    }

    /**
     * Every formula-backed product for a crop, across both seasons.
     *
     * Used when the farmer named a crop but not a season. Listing both is a
     * lookup, not a guess — the season each product is recommended for is
     * carried on the card so the answer stays honest.
     *
     * @return Collection<int, Product>
     */
    public function forCrop(string $crop, ?User $user = null): Collection
    {
        $products = $this->formulaQuery($user)
            ->whereRaw('LOWER(decision_formulas.crop_type) = ?', [mb_strtolower(trim($crop))])
            ->distinct()
            ->get();

        return $this->spreadAcrossStores($products, self::MAX_RESULTS);
    }

    /**
     * The season labels a product is actually formula-backed for. Lets an
     * answer say "recommended for the wet season" without inventing it.
     *
     * @return array<int, string>
     */
    public function seasonsFor(Product $product): array
    {
        return $product->relationLoaded('decisionFormulas')
            ? $product->decisionFormulas->pluck('season')->unique()->values()->all()
            : $product->decisionFormulas()->pluck('season')->unique()->values()->all();
    }

    /**
     * The trusted shape the frontend renders a card from.
     *
     * Every field here is read from the database or produced by the router.
     * The `price` string keeps the format the existing carousel prints, while
     * `price_value` carries the raw float so tests and the response validator
     * can compare against the database without parsing formatted money.
     *
     * @return array<string, mixed>
     */
    public function card(Product $product, ?User $user = null): array
    {
        $gallery = $product->gallery;

        return [
            'id'            => (int) $product->id,
            'name'          => $product->name,
            'display_name'  => $product->display_name,
            'brand'         => $product->brand,
            'category'      => $product->category_display_name,
            'price'         => number_format((float) $product->price, 2),
            'price_value'   => (float) $product->price,
            'price_unit'    => $product->price_unit ?? 'each',
            'package'       => $product->size_label ?? '',
            'stock'         => $product->status === 'out_of_stock' ? 'Out of Stock' : $product->stock_status,
            'in_stock'      => $product->status === 'active' && (int) $product->stock_quantity > 0,
            'store'         => $product->storeOwner?->name ?? '',
            'description'   => mb_strimwidth((string) $product->description, 0, 400, '…'),
            'image'         => $gallery->isNotEmpty()
                ? asset('storage/' . $gallery->first()->file_path)
                : asset('images/product.png'),
            'images'        => $gallery->map(fn ($img) => asset('storage/' . $img->file_path))->values(),
            // Generated by the application, never by the model.
            'url'           => $this->urlFor($product, $user),
        ];
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<int, array<string, mixed>>
     */
    public function cards(Collection $products, ?User $user = null, ?int $limit = null): array
    {
        return $products
            ->take($limit ?? self::MAX_RESULTS)
            ->map(fn (Product $product) => $this->card($product, $user))
            ->values()
            ->all();
    }

    /**
     * Where a farmer buys a product, versus where a store owner manages it.
     * Kept here so the two branches can never disagree with the routes file.
     */
    private function urlFor(Product $product, ?User $user): string
    {
        return ($user?->role ?? auth()->user()?->role) === 'store_owner'
            ? route('store.inventory.index')
            : route('farmer.products.show', $product->id);
    }

    /**
     * @return Collection<int, Product>
     */
    private function baseQuery(?User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = Product::active()->with(['category', 'storeOwner', 'images']);

        if (($user ?? auth()->user())?->role === 'store_owner') {
            $query->where('user_id', ($user ?? auth()->user())->id);
        }

        return $query;
    }

    /**
     * baseQuery() narrowed to the products that carry an approved decision
     * formula, which is what makes a product crop-compatible.
     *
     * The select is spelled out because `products` and `decision_formulas`
     * both carry `id`, `created_at` and `updated_at`; a bare `select *` across
     * that join silently lets the wrong table's columns win depending on the
     * driver.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function formulaQuery(?User $user): \Illuminate\Database\Eloquent\Builder
    {
        return $this->baseQuery($user)
            ->select([
                'products.id', 'products.user_id', 'products.category_id', 'products.name',
                'products.brand', 'products.sku', 'products.product_type', 'products.description',
                'products.package_size', 'products.package_unit', 'products.packaging_type',
                'products.price', 'products.price_unit', 'products.stock_level',
                'products.stock_quantity', 'products.stock_unit', 'products.reorder_threshold',
                'products.views_count', 'products.recommended_season', 'products.status',
                'products.category', 'products.created_at', 'products.updated_at',
            ])
            ->join('decision_formulas', 'decision_formulas.product_id', '=', 'products.id');
    }

    /**
     * Split a question into the tokens worth searching on.
     *
     * Conjunctions, question words and the intent vocabulary are dropped so
     * "do you have any urea products?" searches for "urea products" rather
     * than for "do".
     *
     * @return array<int, string>
     */
    public function keywords(string $query): array
    {
        $normalised = $this->normalizeKeyword(mb_strtolower($query));

            $stopWords = [
                'do', 'you', 'have', 'has', 'any', 'some', 'is', 'are', 'the', 'a', 'an',
                'of', 'for', 'with', 'and', 'or', 'to', 'in', 'on', 'me', 'i', 'we', 'it',
                'what', 'which', 'where', 'can', 'could', 'would', 'please', 'show', 'list',
                'give', 'tell', 'find', 'search', 'product', 'products', 'item', 'items',
                'available', 'stock', 'in', 'price', 'cost', 'much', 'how', 'there', 'that',
                'this', 'buy', 'sell', 'get', 'need', 'want', 'recommend', 'suggest', 'best',
                // Verbs and filler that describe the conversation rather than the
                // product. "Where can I see the urea?" is a question about urea;
                // requiring a product to contain the word "see" emptied the result.
                'see', 'view', 'review', 'open', 'look', 'browse', 'using', 'use', 'used',
                'current', 'currently', 'now', 'today', 'tonight', 'does', 'did', 'should',
                'will', 'shall', 'may', 'might', 'must', 'had', 'more', 'most', 'less',
                'than', 'then', 'also', 'still', 'just', 'only', 'my', 'our', 'your',
                'about', 'from', 'into', 'over', 'under', 'between', 'during', 'before',
                'after', 'up', 'down', 'out', 'off', 'again', 'once', 'here', 'same',
                'other', 'others', 'another', 'each', 'every', 'both', 'few', 'too',
                'very', 'am', 'was', 'were', 'been', 'being', 'have', 'sell', 'order',
            ];

        $tokens = preg_split('/[^\p{L}\p{N}\-]+/u', $normalised, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $keywords = array_values(array_filter(
            array_map(fn ($token) => $this->normalizeKeyword($token), $tokens),
            fn ($token) => $token !== '' && mb_strlen($token) > 1 && ! in_array($token, $stopWords, true)
        ));

        return array_values(array_unique($keywords));
    }

    /**
     * Merchants type en dashes, non-breaking hyphens and ideographic spaces
     * interchangeably with plain ASCII, which is why a literal `like` on the
     * raw string misses rows that are plainly there.
     */
    private function normalizeKeyword(string $keyword): string
    {
        return trim(str_replace(
            ["\u{2010}", "\u{2011}", "\u{2012}", "\u{2013}", "\u{2014}", "\u{2212}", "\u{00A0}", "\u{3000}"],
            ['-', '-', '-', '-', '-', '-', ' ', ' '],
            $keyword
        ));
    }

    /**
     * LIKE wildcards inside user input are literal characters to a farmer
     * looking for a product called "100%_organic", not a pattern to match.
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int, int>
     */
    private function cleanIds(array $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn ($id) => is_numeric($id) ? (int) $id : null, $ids),
            static fn ($id) => $id !== null && $id > 0
        )));
    }

    /**
     * Interleave matches so the carousel isn't dominated by whichever store
     * happens to sort first. Preserves relevance ordering within each store.
     *
     * @param  Collection<int, Product>  $products
     * @return Collection<int, Product>
     */
    private function spreadAcrossStores(Collection $products, int $max): Collection
    {
        if ($products->isEmpty()) {
            return $products;
        }

        $buckets = [];

        foreach ($products as $product) {
            $buckets[$product->storeOwner?->id ?? 'none'][] = $product;
        }

        $buckets = array_values($buckets);
        $deepest = 0;

        foreach ($buckets as $bucket) {
            $deepest = max($deepest, count($bucket));
        }

        $result = collect();

        // Round-robin by position rather than by draining each bucket: one
        // store with a single matching product must contribute that product
        // once, not once per remaining slot.
        for ($position = 0; $position < $deepest && $result->count() < $max; $position++) {
            foreach ($buckets as $bucket) {
                if (! array_key_exists($position, $bucket)) {
                    continue;
                }

                $result->push($bucket[$position]);

                if ($result->count() >= $max) {
                    break 2;
                }
            }
        }

        return $result->values();
    }
}
