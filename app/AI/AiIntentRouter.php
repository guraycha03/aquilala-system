<?php

namespace App\AI;

use App\Services\CropService;
use App\Services\FarmingKnowledgeService;
use Illuminate\Support\Str;

/**
 * AiIntentRouter
 *
 * Decides, in PHP, which Laravel service owns a question. The model is not
 * consulted and cannot override the result.
 *
 * The router does three jobs:
 *   1. classify the message into one of the AiIntent values,
 *   2. pull out the slots the services need (crop, season, area, soil,
 *      weather, and which products "it" refers to),
 *   3. report what is still missing, so the caller can ask rather than guess.
 *
 * Order matters and is deliberate: the more specific a pattern is, the earlier
 * it is tested. "How much fertilizer for 2 ha of rice?" must reach
 * COST_ESTIMATION, not PRODUCT_SEARCH just because it contains "fertilizer".
 */
class AiIntentRouter
{
    /**
     * Vocabulary for the "what should I buy" family of questions.
     *
     * Wrapped in a non-capturing group on purpose. These fragments are composed
     * into larger patterns, and an ungrouped alternation would give the trailing
     * part of the composed pattern a top-level `|`, silently turning the whole
     * check into "does the message contain an input word" — which is how
     * "do you have rice products?" ended up classified as a recommendation.
     */
    private const INPUT_WORDS = '(?:fertilizers?|inputs?|products?|seeds?|seedlings?|pesticides?|herbicides?|fungicides?|insecticides?|brands?|urea|npk|complete|mop|potash|ammonium|soil\s*amendments?|tools?|equipment|machinery|sprayers?|pumps?|irrigation|storage|feed|nursery|crop\s+protection|containers?|crates?)';

    /**
     * Input *names* rather than input categories: things a farmer would ask the
     * price of. "fertilizers" is a category and cannot be priced; "urea" and
     * "16-14-14" are rows in the catalogue with a price attached.
     *
     * A question naming one of these, and nothing else to calculate from, is a
     * price lookup on a catalogue row. Without this the router read
     * "how much is the urea?" as a cost estimate, asked for a farm area, and
     * then asked again on every following turn — the same question never
     * produced a different answer because the answer never depended on it.
     */
    private const SPECIFIC_PRODUCTS = '(?:urea|npk|mop|potash|ammonium|complete\s+(?:fertilizer|fertiliser)|\d{1,2}\s*[\-\/]\s*\d{1,2}\s*[\-\/]\s*\d{1,2})';

    /**
     * A farmer naming a product that the catalogue may not hold.
     *
     * "Super Corn Fertilizer 5000" contains the word "corn", and corn is a crop
     * with a full compatibility list, so the crop route answered it with ten
     * unrelated products. That is the failure #38 Test 12 is written against:
     * the farmer asked for one specific thing and was handed a shelf. A brand
     * qualifier, a model number, or a capitalized multi-word name is the signal
     * that the question is about a product row and not about a crop.
     */
    private const NAMED_PRODUCT_PATTERNS = [
        // A model or grade number: "Fertilizer 5000", "Urea 46".
        '/\b\d{3,}\b/',
        // "complete 14-14-14", "npk 16-16-16".
        '/\b\d{1,2}\s*[\-\/]\s*\d{1,2}\s*[\-\/]\s*\d{1,2}\b/',
        // A modifier in front of the product noun: "super corn fertilizer",
        // "premium urea", "organic fertilizer".
        '/\b(super|premium|organic|special|advanced|new|brand|pro|max|ultra|best)\b[^.?!]{0,30}\b(fertilizer|fertiliser|product|input|seed|brand|pesticide|herbicide)\b/i',
        // Quoted or all-caps product names.
        '/[""][^""]{3,}[""]/',
        '/\b[A-Z][A-Z0-9]{3,}\b/',
    ];

    /**
     * True when the farmer named a product rather than a crop.
     */
    private function namesASpecificProduct(string $text): bool
    {
        foreach (self::NAMED_PRODUCT_PATTERNS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * A greeting, and nothing else.
     *
     * Kept apart from the capability questions on purpose. "Hi" used to fall
     * through to the help answer, which is a wall of text describing everything
     * the assistant does and cannot do — the wrong reply to someone who has
     * only said hello, and a poor first impression of the whole system.
     */
    private const GREETING_PATTERNS = [
        '/^\s*(hi|hello|hey|yo|kumusta|magandang\s+(umaga|hapon|gabi)|good\s+(morning|afternoon|evening|day))\b/i',
        '/^\s*(hi|hello|hey)\s+(there|ka)\b/i',
    ];

    /**
     * Questions about crops themselves: what Aquilala supports, what can be
     * planted, what is in the directory. No product is involved in these, and
     * they used to end in a refusal, because the router had nothing that
     * matched them and fell through to the unsupported answer.
     */
    private const CROP_DISCOVERY_PATTERNS = [
        '/\b(what|which|any)\s+(crops?|fruits?|fruit\s+crops?|vegetables?|plants?|seeds?|varieties)\b/i',
        '/\b(crops?|fruits?|vegetables?|plants?)\s+(are|is)\s+(available|supported|in\s+the\s+catalog|in\s+the\s+system)\b/i',
        '/\b(do|does)\s+(you|aquilala|it)\s+(support|have|carry|offer|list)\b/i',
        '/\bwhat\s+(do|does)\s+(you|aquilala)\s+(have|offer|carry)\b/i',
        '/\b(what|which)\s+(crops?|fruits?|vegetables?)\b[^.?!]{0,40}\b(is|are)\s+(good|best|easy|worth)\b/i',
    ];

    /**
     * A crop chosen for a purpose rather than a listing of them. "What fruit
     * should I plant?" is a decision, and the answer is the supported choices
     * plus what the application's own records say about them — not a refusal,
     * and not a single confident winner either.
     */
    private const CROP_CHOICE_PATTERNS = [
        '/\b(what|which)\s+(crops?|fruits?|fruit\s+crops?|vegetables?|plants?)\b[^.?!]{0,40}\b(should|best|good|to\s+plant|to\s+grow|to\s+sow|recommend|suitable|worth|ideal)\b/i',
        '/\b(suggest|recommend|advise|tell\s+me)\b[^.?!]{0,40}\b(crops?|fruits?|vegetables?|plants?|seeds?)\b/i',
        '/\b(most|best|top|greatest)\s+\w*\s*(crop|fruit|vegetable|plant|seed)\b/i',
        // "What should I plant?" asks for a decision, not a list. Answering it
        // by dumping the whole directory was the other half of the same
        // problem: a listing answers a question nobody asked.
        '/\bwhat\s+should\s+i\s+(plant|grow|sow|raise|try)\b/i',
        // "What's good to plant in my backyard right now?" is the specification's
        // own recommendation example. The contraction used to slip past every
        // pattern above and land on crop discovery, so a recommendation
        // question was answered with a directory listing.
        '/\bwhat(?:[\'’]?s|\s+is|\s+are)\s+(good|the\s+best|better)\s+to\s+(plant|grow|sow|raise)\b/i',
        '/\bwhat(?:[\'’]?s|\s+is|\s+are)\s+good\s+(to\s+plant|to\s+grow|to\s+sow)\b/i',
        '/\bwhat\s+(is|are)\s+(a\s+)?good\s+(crop|fruit|vegetable|plant|seed)s?\s+(to|for)\s+(plant|my|this)\b/i',
        '/\b(ano|anu)\b[^.?!]{0,40}\b(itanim|tanim|pwede|maaari)\b/i',
    ];

    /**
     * Words that ask for a personal preference as though it were a fact.
     *
     * The assistant does not crown a winner on any of them. It says plainly
     * that the comparison is a matter of taste, and answers the part it can
     * answer: what Aquilala actually supports.
     */
    private const SUBJECTIVE_PATTERNS = [
        '/\b(tasty|tastiest|tastier|delicious|flavour|flavor|sweetest|sweetness|yummiest|juiciest|scrumptious)\b/i',
        '/\b(most|best|top|greatest)\b[^.?!]{0,30}\b(fruit|crop|vegetable|plant|seed|taste)\b/i',
    ];

    /**
     * Ranking requests the system cannot answer honestly. The catalogue holds a
     * typical yield per crop, but nothing that compares one crop's yield to
     * another's under the same conditions, so any "highest" answer would be
     * arithmetic on numbers that were never measured side by side.
     */
    private const COMPARISON_PATTERNS = [
        'yield'    => '/\b(highest|best|most|greatest|top|max(imum)?|better)\b[^.?!]{0,30}\b(yield|yielding|productivity|output|harvest)\b/i',
        'profit'   => '/\b(highest|best|most|greatest|top|most)\b[^.?!]{0,30}\b(profit|profitable|profitability|returns?|income|earnings)\b/i',
        'value'    => '/\b(cheapest|most\s+affordable|best\s+value|lowest\s+price)\b/i',
    ];

    /**
     * Questions about the assistant itself.
     */
    private const SYSTEM_HELP_PATTERNS = [
        '/\b(who|what)\s+are\s+you\b/i',
        '/\bwhat\s+can\s+you\s+(do|help)\b/i',
        '/\bhow\s+(do\s+you|does\s+this)\s+work\b/i',
        '/\byour\s+(capabilities|features|limits|limitations)\b/i',
        '/\bhelp\s*(me)?\s*(use|out)\b/i',
        '/\bthanks|thank\s+you\b/i',
        '/\bmay\s+tanong\b/i',
        '/\bhave\s+(a\s+)?question\b/i',
        '/\b(are\s+you|is\s+this)\s+(working|available|online|okay|ok|ai|an?\s+ai|an?\s+assistant)\b/i',
        '/\bai\s+ka\b/i',
        '/\bikaw\s+ba\b/i',
    ];

    /**
     * Things the assistant has no data source for. Checked early so a
     * "what's the weather tomorrow" never reaches a general farming branch on
     * the strength of the word "weather".
     *
     * Note: these patterns are checked in classify() before any other intent
     * routing. A match immediately returns UNSUPPORTED so the assistant replies
     * with the deterministic "I focus on Aquilala farming..." answer rather
     * than pulling database data.
     */
    private const UNSUPPORTED_PATTERNS = [
        // Live weather is out of scope in any phrasing. The application knows
        // the Bicol climate calendar, which is a seasonal summary and not a
        // forecast — answering "will it rain tomorrow" from it would be a
        // guess dressed as a fact.
        '/\b(weather|forecast|temperature|rainfall|humidity|wind\s+speed|heatwave|typhoon\s+warning)\b/i',
        // Financial/market data out of scope
        '/\b(stock\s+market|crypto|bitcoin|exchange\s+rate|dollar\s+rate|pesos?\s+to\s+dollar)\b/i',
        '/\b(price\s+of\s+rice\s+in\s+the\s+market|market\s+price|palay\s+price)\b/i',
        // Non-farming topics
        '/\b(write|compose|generate)\s+(me\s+)?(a\s+|an\s+)?(poem|essay|song|story|code|program|script|email|letter)\b/i',
        '/\b(debug|refactor|unit\s+test|stack\s*trace|exception)\b/i',
        // Personal/legal/medical topics
        '/\b(medical|doctor|hospital|diagnos(is|e)|symptoms?\s+of\s+(a\s+)?(human|child|adult))\b/i',
        '/\b(loan|credit|salary|job\s+opening|resume|certificate|training)\b/i',
        '/\b(legal|lawyer|court|permit|tax)\b/i',
    ];

    /**
     * Follow-up references back to the previous turn's products.
     */
    private const FOLLOW_UP_PATTERN = '/\b(it|its|them|they|that|those|this|these|the\s+same|again)\b|\b(how\s+much|price|see|view|show|review|check|buy|get|order)\b/i';

    public function __construct(
        private readonly FarmingKnowledgeService $knowledge,
        private readonly CropService $crops,
    ) {
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{intent: string, slots: array<string, mixed>, missing: array<int, string>, follow_up: bool, subjective: bool}
     */
    public function route(string $message, AiConversationContext $context = new AiConversationContext, array $history = []): array
    {
        $text = trim($message);
        $normalized = $this->normalize($text);

        $slots = [
            'crop'           => $this->detectCrop($normalized),
            'season'         => $this->detectSeason($normalized),
            'area'           => $this->detectArea($normalized),
            'soil'           => $this->detectSoil($normalized),
            'weather'        => $this->detectWeather($normalized),
            'product_ids'    => [],
            // "What fruits can I plant?" names a category, not a crop. The
            // crops directory is what turns the word into a real set of rows.
            'crop_category'  => $this->detectCropCategory($normalized),
            // The month a seasonal question is about, resolved against the
            // application's own Bicol climate calendar.
            'month'          => $this->detectMonthWindow($normalized),
        ];

        // What this message said for itself, before any inheritance. A slot the
        // farmer did not state must not be treated as if they had: an area
        // carried over from the previous turn cannot turn "how much is urea?"
        // into a cost estimate.
        $detected = [
            'crop'   => $slots['crop'],
            'season' => $slots['season'],
            'area'   => $slots['area'],
        ];

        // A follow-up inherits the crop and season it was last asked about, so
        // "how much is it?" does not need to repeat "for rice".
        $slots['crop'] = $slots['crop'] ?? $context->lastCrop;
        $slots['season'] = $slots['season'] ?? $context->lastSeason;
        $slots['area'] = $slots['area'] ?? $context->lastArea;

        $intent = $this->classify($normalized, $context, $slots, $detected);

        // Only a message that actually points backwards may claim the previous
        // turn's products. Otherwise "give me the best product" would silently
        // inherit whatever was last discussed and answer the wrong question.
        $followUp = $this->isFollowUp($normalized);

        if ($followUp && $context->lastProductIds !== []) {
            $slots['product_ids'] = $context->lastProductIds;
        }

        return [
            'intent'     => $intent,
            'slots'      => $slots,
            'missing'    => $this->missingFor($intent, $slots),
            'follow_up'  => $followUp,
            // Whether the farmer asked for a personal preference as though it
            // were a fact. The answer has to acknowledge that it is one.
            'subjective' => $this->isSubjective($normalized),
            // A request to rank one crop above another on a measure the
            // application does not compare. #37 bans the words, #39 requires
            // saying the comparison is unavailable, and neither is possible
            // without noticing the question is a comparison at all.
            'comparison' => $this->unrankedComparison($normalized),
            // Whether the farmer named a product rather than a crop, which
            // decides if the crop's compatibility list is an answer or a
            // substitute.
            'names_specific_product' => $this->namesASpecificProduct($text),
        ];
    }

    /**
     * "Which one has the highest yield?", "what gives the best yield?" — a
     * ranking request with no ranking behind it.
     *
     * @return array{measure: string, subject: string}|null
     */
    private function unrankedComparison(string $text): ?array
    {
        foreach (self::COMPARISON_PATTERNS as $measure => $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return ['measure' => $measure, 'subject' => trim($text, " \t\n\r\0\x0B?.!")];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $slots
     * @param  array<string, mixed>  $detected  slots as stated in this message
     */
    private function classify(string $text, AiConversationContext $context, array $slots, array $detected = []): string
    {
        $detected = $detected + ['crop' => null, 'season' => null, 'area' => null];

        // Check for empty utterances first - these are not greetings, just
        // the farmer pausing mid-thought or sending an empty message.
        if ($this->isEmptyUtterance($text)) {
            return AiIntent::CLARIFICATION;
        }

        foreach (self::SYSTEM_HELP_PATTERNS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return AiIntent::SYSTEM_HELP;
            }
        }

        foreach (self::UNSUPPORTED_PATTERNS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return AiIntent::UNSUPPORTED;
            }
        }

        // Safety net: directly check for weather and other out-of-scope topics
        // that the regex patterns might miss due to edge cases in normalization.
        // This prevents the assistant from pulling database data for unrelated queries.
        $lower = Str::lower($text);
        if (preg_match('/\bweather\b/', $lower) === 1) {
            return AiIntent::UNSUPPORTED;
        }
        if (preg_match('/\b(forecast|temperature|rainfall|humidity)\b/', $lower) === 1) {
            return AiIntent::UNSUPPORTED;
        }

        // The farmer's own quotation is a real, retrievable record. Hand it to
        // the quotation branch rather than trying to answer from memory.
        if ($this->mentionsQuotation($text)) {
            return AiIntent::QUOTATION;
        }

        // A referential follow-up resolves to the products already on the
        // table. Tested before the calculation branch, because "how much is
        // it?" and "can I review it?" both carry a money or a review word and
        // both inherit the crop from the previous turn — which is how a
        // question about a product turns into a demand for a farm area.
        //
        // A message that states a crop or an area of its own is a new question,
        // not a follow-up, whatever else it contains.
        if ($context->lastProductIds !== []
            && $this->isFollowUp($text)
            && $detected['crop'] === null
            && $detected['area'] === null) {
            return AiIntent::PRODUCT_SEARCH;
        }

        // "Do you have mango products?", "is the urea in stock?" — a question
        // about what the catalogue holds. Tested before both the calculation
        // and the recommendation branches, because the recommendation branch
        // otherwise swallowed it: "products for mango" matches the "…for <crop>"
        // shape, and the farmer was asked for a farm area when all they wanted
        // was to know whether the products exist.
        if ($this->mentionsCatalogAvailability($text)) {
            return AiIntent::PRODUCT_SEARCH;
        }

        if ($this->mentionsCost($text)) {
            // A price asked for by name is a fact about one catalogue row, not
            // a calculation. Treating it as a cost estimate is what produced
            // the same "what is your farm area?" reply to every question in the
            // conversation.
            if ($this->isNamedProductPriceQuestion($text, $detected)) {
                return AiIntent::PRODUCT_SEARCH;
            }

            // "How much ... for <crop>/<area>" is a calculation. Tested before
            // the recommendation branch so a priced question is never answered
            // with a bare product list. An inherited crop or area is enough to
            // continue, which is what makes "how much will that cost?"
            // answerable at all.
            if ($slots['crop'] !== null || $slots['area'] !== null) {
                return AiIntent::COST_ESTIMATION;
            }
        }

        if ($this->mentionsRecommendation($text)) {
            return AiIntent::PRODUCT_RECOMMENDATION;
        }

        // A named product is a catalogue lookup before it is a definition.
        // "How much is the urea?" asks for a price on a product, and must not
        // be answered as an explanation of what urea is.
        if ($this->mentionsCatalogLookup($text)) {
            return AiIntent::PRODUCT_SEARCH;
        }

        if ($this->mentionsFertilizerExplanation($text)) {
            return AiIntent::FERTILIZER_ADVICE;
        }

        // ── Positive routes ────────────────────────────────────────
        //
        // Everything below this line is a question the application can answer
        // and used to refuse. The order is the fallback hierarchy: a month
        // before a category, a category before a plain crop word, because the
        // more specific answer is the more useful one.

        // "What can I plant this October?" — a month plus something to plant,
        // answered from the formulas that exist for the season it falls in.
        if (($slots['month'] ?? null) !== null && $this->mentionsPlanting($text)) {
            return AiIntent::SEASONAL_RECOMMENDATION;
        }

        // "What fruit should I plant?", "suggest the most tasty fruit" — a
        // decision, answered with the supported choices and an honest word about
        // taste being the farmer's to judge.
        if ($this->mentionsCropChoice($text)) {
            return AiIntent::CROP_RECOMMENDATION;
        }

        // "What crops do you have?", "what fruits does Aquilala support?" —
        // answered from the crops directory and nothing else.
        if ($this->mentionsCropDiscovery($text)) {
            return AiIntent::CROP_DISCOVERY;
        }

        if ($this->mentionsCultivation($text)) {
            return AiIntent::FARMING_ADVICE;
        }

        // The last positive step before giving up. A crop named on its own is a
        // question about that crop; a crop *word* with nothing to narrow it is a
        // question about crops.
        if (($slots['crop'] ?? null) !== null) {
            return AiIntent::CROP_ADVICE;
        }

        // A bare crop word with nothing to narrow it is a crop search, not a
        // discovery question: the farmer named a crop and wants that crop.
        if ($this->mentionsCropWords($text)) {
            return AiIntent::CROP_SEARCH;
        }

        if ($this->mentionsFarming($text)) {
            return AiIntent::GENERAL_FARMING;
        }

        // A greeting is a greeting, and gets a greeting back. Tested after
        // everything else because we want to make sure the farmer's actual
        // question is answered first. A pure greeting with no question content
        // will fall through to here.
        foreach (self::GREETING_PATTERNS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return AiIntent::GREETING;
            }
        }

        return AiIntent::UNSUPPORTED;
    }

    // ────────────────────────────────────────────────────────────
    //  Intent predicates
    // ────────────────────────────────────────────────────────────

    private function mentionsQuotation(string $text): bool
    {
        return preg_match('/\b(quotation|quote|estimate[ds]?)\b/i', $text) === 1
            && preg_match('/\b(my|create|generate|new|make|open|show|view|regenerate|again)\b/i', $text) === 1
            || preg_match('/\bmy\s+(quotation|quote|estimate)s?\b/i', $text) === 1;
    }

    private function mentionsCost(string $text): bool
    {
        return preg_match('/\b(how\s+much|cost|costs|costing|budget|price\s+of|prices|total|php|₱|peso|pesos)\b/i', $text) === 1
            || preg_match('/₱/u', $text) === 1;
    }

    /**
     * "What should I use", "which fertilizer is best", "fertilizers for rice",
     * and the bare "give me the best product" — which is a recommendation
     * request with no crop attached, and must be answered by asking.
     */
    private function mentionsRecommendation(string $text): bool
    {
        $hasInput = preg_match('/\b' . self::INPUT_WORDS . '\b/i', $text) === 1;

        if (! $hasInput) {
            return false;
        }

        // "...for/of/on <something>" ties the request to a crop or a use.
        if (preg_match('/\b' . self::INPUT_WORDS . '\b[^.?!]{0,24}\b(for|of|on|to\s+use\s+for|to\s+apply)\b/i', $text) === 1) {
            return true;
        }

        return preg_match('/\b(recommend(?:ation)?s?|suggest(?:ion)?s?|best|which|what|should\s+i|need|want|appropriate|suitable|right)\b/i', $text) === 1;
    }

    /**
     * Questions about what a fertilizer *is* or *does*, as opposed to which one
     * to buy. These are answered from approved knowledge, or not at all.
     */
    private function mentionsFertilizerExplanation(string $text): bool
    {
        // The subject has to be an input. "What is the weather" is not a
        // question about urea, however well it fits the shape.
        if (preg_match('/\b' . self::INPUT_WORDS . '\b/i', $text) !== 1) {
            return false;
        }

        if (preg_match('/\b' . self::INPUT_WORDS . '\b[^.?!]{0,24}\b(for|of|on|to\s+use\s+for|to\s+apply)\b/i', $text) === 1) {
            return false;
        }

        if (preg_match('/\b(recommend|suggest|best|which|should\s+i|buy|price|cost|how\s+much|available|stock|order|review|where)\b/i', $text) === 1) {
            return false;
        }

        // An explicit explanation cue is required. A bare product word is a
        // request for the product, not a question about what it means.
        return preg_match('/\b(what\s+is|what\s+are|what\s+does|explain|meaning|means|difference|about|npk|urea|complete\s+fertilizer|mop|potash|ammonium)\b/i', $text) === 1;
    }

    /**
     * "Do you have mango products?", "any urea?", "how much is the urea?",
     * "where can I see the complete fertilizer", "is the urea in stock?".
     *
     * The lookup vocabulary carries the money and location phrasings too,
     * because a question about a price or a link for a named product is a
     * question about the catalog row — that is where the answer lives.
     */
    private function mentionsCatalogLookup(string $text): bool
    {
        // A bare product noun is a lookup. "knapsack sprayer" is a thing in the
        // catalogue, and being told the assistant cannot help with it is a
        // false negative on the merchant's own shelf. The head noun has to be
        // the catalogue word so that a bare crop name still means the crop.
        if (preg_match('/^\s*(?:\w+[-\s]){0,2}' . self::INPUT_WORDS . '\s*\??\s*$/i', $text) === 1) {
            return true;
        }

        return preg_match('/\b(do\s+you\s+have|have\s+you\s+got|is\s+there|any\s+\w*|available|in\s+stock|out\s+of\s+stock|show\s+me|list|find|catalog|catalogue|search|look\s+for|sku|price|price\s+of|cost|costs|how\s+much|brand|where\s+(can|do)\s+(i|we)|link|url)\b/i', $text) === 1
            && preg_match('/\b' . self::INPUT_WORDS . '\b|\b' . self::cropNamePattern() . '\b/i', $text) === 1;
    }

    /**
     * "Do you have mango products?", "is the urea in stock?", "where can I see
     * the complete fertilizer?" — an availability or whereabouts question about
     * a crop the application knows or a product it can name.
     *
     * A category on its own ("what fertilizers do you have?") is deliberately
     * not enough: that is a genuine "what should I buy" question, and asking
     * which crop is the more useful answer.
     *
     * @see mentionsCatalogLookup() for the broader lookup vocabulary
     */
    private function mentionsCatalogAvailability(string $text): bool
    {
        $cue = '/\b(do\s+you\s+(have|sell|carry|stock)|have\s+you\s+got|is\s+there\s+any|are\s+there\s+any|in\s+stock|out\s+of\s+stock|available|show\s+me|list|where\s+(can|do)\s+(i|we)|link|url)\b/i';

        if (preg_match($cue, $text) !== 1) {
            return false;
        }

        return $this->detectCrop($text) !== null
            || preg_match('/\b' . self::SPECIFIC_PRODUCTS . '\b/i', $text) === 1;
    }

    /**
     * "How much is the urea?", "what's the price of 16-14-14?" — money plus a
     * product name, with nothing to calculate from.
     *
     * The negative cases are the ones that matter. A crop named in the same
     * message ("how much urea for rice?") or an area ("how much urea for 2
     * hectares?") is a quantity, and a need-word ("how much urea do I need?")
     * is a request for advice; both belong to the calculation branch, which
     * asks for a farm area instead of quoting a price.
     *
     * @param  array<string, mixed>  $detected
     */
    private function isNamedProductPriceQuestion(string $text, array $detected): bool
    {
        if (preg_match('/\b' . self::SPECIFIC_PRODUCTS . '\b/i', $text) !== 1) {
            return false;
        }

        if (($detected['crop'] ?? null) !== null || ($detected['area'] ?? null) !== null) {
            return false;
        }

        return preg_match('/\b(need|needs|needed|require|required|should|would|enough|how\s+many|quantity|quantities|per\s+hectare|per\s+ha|per\s+acre|total|budget|afford)\b/i', $text) !== 1;
    }

    /**
     * "What crops can I plant?", "what fruits does Aquilala support?", "what do
     * you have to plant?"
     */
    private function mentionsCropDiscovery(string $text): bool
    {
        foreach (self::CROP_DISCOVERY_PATTERNS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * A crop the farmer wants to be advised between, rather than a list of
     * crops or a catalogue of products.
     */
    private function mentionsCropChoice(string $text): bool
    {
        foreach (self::CROP_CHOICE_PATTERNS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Anything to do with growing something, as opposed to buying something.
     */
    private function mentionsPlanting(string $text): bool
    {
        return preg_match('/\b(plant|planting|planted|grow|growing|grows|sow|sow\w*|crop|crops|seed|seeds|variety|varieties)\b/i', $text) === 1;
    }

    /**
     * Words that mean the farmer is asking about crops as a subject. Used only
     * to reach a positive route on the way down, never to claim an intent on
     * its own.
     */
    private function mentionsCropWords(string $text): bool
    {
        return preg_match('/\b(crop|crops|fruit|fruits|vegetable|vegetables|plant|plants|backyard|garden|farm\s+plot)\b/i', $text) === 1;
    }

    /**
     * "Tastiest", "sweetest", "best" — a preference dressed as a fact.
     */
    private function isSubjective(string $text): bool
    {
        foreach (self::SUBJECTIVE_PATTERNS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * The crop *category* a question is about, when the farmer named one the
     * crops directory actually uses: "fruits", "vegetables", "root crops".
     *
     * @return array<string, mixed>|null
     */
    private function detectCropCategory(string $text): ?array
    {
        $categories = $this->crops->categories();

        if ($categories === []) {
            return null;
        }

        // Farmers say "fruits" and "vegetables". The directory says "Fruit" and
        // "Vegetable", so an exact-word match against the stored spelling found
        // nothing, and "what fruits can I plant?" answered with every crop the
        // system has. Stems are grouped so that a plural of any category
        // resolves, and the answer is then written back in the directory's own
        // spelling.
        $groups = [];

        foreach ($categories as $category) {
            $stem = mb_strtolower((string) preg_replace('/s$/i', '', $category));

            $groups[$stem] = $category;
        }

        $pattern = '/\b('
            . implode('|', array_map(static fn (string $stem) => preg_quote($stem, '/') . 's?', array_keys($groups)))
            . ')\b/i';

        if (preg_match($pattern, $text, $matches) !== 1) {
            return null;
        }

        $matched = mb_strtolower($matches[1]);
        $stem = (string) preg_replace('/s$/i', '', $matched);

        return isset($groups[$stem]) ? ['name' => $groups[$stem]] : null;
    }

    /**
     * The month a seasonal question is about.
     *
     * "This October" resolves to October. "This month" and "now" resolve to
     * whatever the calendar says it is. A bare season word is not a month, and
     * the season those words mean is already a separate slot.
     *
     * @return array{month: int, month_name: string, season: string}|null
     */
    private function detectMonthWindow(string $text): ?array
    {
        foreach (preg_split('/[^\p{L}]+/u', $text) ?: [] as $word) {
            $month = $this->crops->seasonForMonthName($word);

            if ($month !== null) {
                return $month;
            }
        }

        if (preg_match('/\b(this|current|coming|next)\s+(month|season)\b/i', $text) === 1) {
            return $this->crops->currentMonth();
        }

        return null;
    }

    private function mentionsCultivation(string $text): bool
    {
        return preg_match('/\b(how\s+(do|to|can)\s+(i|we)?\s*(plant|grow|raise|cultivate|sow|transplant)|planting|when\s+(do|to|should)\s+(i|we)?\s*(plant|sow)|land\s+prep|spacing|seed\s+rate|transplant|harvest\s+time|maturity|magtanim|pagtanim|tanim)\b/i', $text) === 1;
    }

    private function mentionsFarming(string $text): bool
    {
        return preg_match('/\b(crop|farm|farmer|farming|harvest|pest|disease|soil|irrigat\w*|weed\w*|yield|season|climat\w*|sow\w*|seed\w*|rice|corn|mango|banana|vegetable\w*)\b/i', $text) === 1
            || preg_match('/\b' . $this->cropNamePattern() . '\b/i', $text) === 1;
    }

    private function isEmptyUtterance(string $text): bool
    {
        $stripped = preg_replace('/[^\p{L}\p{N}]+/u', '', $text) ?? '';

        // A single character or nothing at all is a pause, not a message.
        // Two-letter greetings like "hi" and "yo" must reach the greeting
        // check, so the threshold is one, not two.
        return $stripped === '' || mb_strlen($stripped) <= 1;
    }

    private function isFollowUp(string $text): bool
    {
        // A follow-up is short and reference-shaped. A long message that merely
        // contains the word "it" is a new question.
        if (mb_strlen($text) > 120) {
            return false;
        }

        $hasReference = preg_match('/\b(it|its|them|they|that\s+(product|item|one|fertilizer|seed)|those|the\s+same|again)\b/i', $text) === 1;
        $hasQuestion = preg_match('/\b(how\s+much|price|see|view|show|review|check|buy|get|order|where|is\s+it|are\s+they)\b/i', $text) === 1;

        return $hasReference || $hasQuestion;
    }

    // ────────────────────────────────────────────────────────────
    //  Slot detection
    // ────────────────────────────────────────────────────────────

    /**
     * Match the crop against the names the application actually knows: the
     * Bulan catalogue, the crops table and the decision formulas. A word the
     * application has no record of is not a crop, however much it looks like
     * one.
     */
    private function detectCrop(string $text): ?string
    {
        $matches = $this->knownCrops();

        // Longest name first, so "sweet potato" wins over "potato" and the
        // farmer is not told their Sweet Potato batch is a Potato batch.
        uasort($matches, fn ($a, $b) => mb_strlen($b) - mb_strlen($a));

        foreach ($matches as $key => $canonical) {
            if (preg_match('/\b' . preg_quote($key, '/') . '\b/u', $text) === 1) {
                return $canonical;
            }
        }

        return null;
    }

    private function detectSeason(string $text): ?string
    {
        if (preg_match('/\b(wet|rainy|rainfall|habagat|amihan|tag-ulan|tagulan)\b/u', $text) === 1) {
            return 'wet';
        }

        if (preg_match('/\b(dry|summer|tag-init|taginit|tag-araw|tagaraw|drought)\b/u', $text) === 1) {
            return 'dry';
        }

        return null;
    }

    /**
     * Farm area, normalised to hectares because that is the unit every formula
     * and every rule in the engine is expressed in.
     */
    private function detectArea(string $text): ?float
    {
        $hectares = $this->matchArea($text, [
            'hectares?' => 1.0,
            '\bhas\b'   => 1.0,
            '\bha\b'    => 1.0,
        ]);

        if ($hectares !== null) {
            return $hectares;
        }

        // Acres are common in farm conversations even in hectares-first
        // countries; converting keeps the farmer from having to do it.
        $acres = $this->matchArea($text, ['acres?' => 0.404686, '\bac\b' => 0.404686]);

        if ($acres !== null) {
            return round($acres, 4);
        }

        $sqm = $this->matchArea($text, ['square\s+met(?:re|er)s?' => 0.0001, 'sq\.?\s*m' => 0.0001]);

        return $sqm === null ? null : round($sqm, 4);
    }

    /**
     * @param  array<string, float>  $units
     */
    private function matchArea(string $text, array $units): ?float
    {
        foreach ($units as $unit => $factor) {
            // "2 ha", "2 hectares", "2-hectare", "2 hectare".
            if (preg_match('/(\d+(?:[.,]\d+)?)\s*-?\s*(' . $unit . ')\b/u', $text, $m) === 1) {
                $value = (float) str_replace(',', '', $m[1]);

                if ($value > 0) {
                    return round($value * $factor, 4);
                }
            }
        }

        return null;
    }

    private function detectSoil(string $text): ?string
    {
        return match (true) {
            (bool) preg_match('/\b(sandy\s+soil|sand\s+soil|sandy)\b/i', $text) => 'sandy',
            (bool) preg_match('/\b(peat\s+soil|peaty|peat|muck\s+soil)\b/i', $text) => 'peat',
            (bool) preg_match('/\b(clay\s*loam|clay|loam)\b/i', $text) => 'clay_loam',
            default => null,
        };
    }

    private function detectWeather(string $text): ?string
    {
        return match (true) {
            (bool) preg_match('/\b(typhoon|storm|heavy\s+rain)\b/i', $text) => 'typhoon',
            (bool) preg_match('/\b(el\s*nino|drought|dry\s+spell)\b/i', $text) => 'el_nino',
            (bool) preg_match('/\b(low\s+rain|less\s+rain|reduced\s+rain)\b/i', $text) => 'low_rain',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $slots
     * @return array<int, string>
     */
    private function missingFor(string $intent, array $slots): array
    {
        $missing = [];

        if (in_array($intent, [AiIntent::PRODUCT_RECOMMENDATION, AiIntent::CROP_ADVICE], true) && $slots['crop'] === null) {
            $missing[] = 'crop';
        }

        if ($intent === AiIntent::COST_ESTIMATION && $slots['area'] === null) {
            $missing[] = 'area';
        }

        // "What should I plant?" with no crop and no category is answerable, but
        // only by guessing at what kind of thing. One short question beats a
        // list of everything the directory holds.
        if ($intent === AiIntent::CROP_RECOMMENDATION
            && ($slots['crop'] ?? null) === null
            && ($slots['crop_category'] ?? null) === null) {
            $missing[] = 'crop_category';
        }

        return $missing;
    }

    // ────────────────────────────────────────────────────────────
    //  Text helpers
    // ────────────────────────────────────────────────────────────

    private function normalize(string $text): string
    {
        $text = Str::lower(trim($text));
        $text = str_replace(
            ["\u{2010}", "\u{2011}", "\u{2012}", "\u{2013}", "\u{2014}", "\u{2212}", "\u{00A0}"],
            ['-', '-', '-', '-', '-', '-', ' '],
            $text
        );

        return preg_replace('/\s+/u', ' ', $text) ?? $text;
    }

    /**
     * @var array<string, string>|null
     */
    private ?array $knownCrops = null;

    /**
     * Crop aliases, lowercased and keyed for regex matching.
     *
     * @return array<string, string>
     */
    private function knownCrops(): array
    {
        if ($this->knownCrops !== null) {
            return $this->knownCrops;
        }

        $map = [];

        foreach ($this->knowledge->cropNames() as $name) {
            $map[$this->slug($name)] = $name;
        }

        $known = array_flip(array_values($map));

        // Filipino common names farmers actually type. Aliased only when the
        // English name is a crop the application already has on record, so an
        // alias can never introduce a crop the catalogue does not know.
        $aliases = [
            'palay' => 'Rice',
            'bigas' => 'Rice',
            'ulo'   => 'Rice',
            'saka'  => 'Rice',
            'mais'  => 'Corn',
            'maize' => 'Corn',
        ];

        foreach ($aliases as $alias => $canonical) {
            if (isset($known[$canonical]) && ! array_key_exists($alias, $map)) {
                $map[$alias] = $canonical;
            }
        }

        return $this->knownCrops = $map;
    }

    private function cropNamePattern(): string
    {
        $keys = array_map('preg_quote', array_keys($this->knownCrops()));

        return $keys === [] ? '(?!)' : '(?:' . implode('|', $keys) . ')';
    }

    private function slug(string $name): string
    {
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower(trim($name))) ?? mb_strtolower(trim($name));

        return trim($slug);
    }
}
