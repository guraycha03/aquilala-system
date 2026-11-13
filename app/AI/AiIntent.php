<?php

namespace App\AI;

/**
 * The closed set of things the Aquilala assistant knows how to be asked.
 *
 * An intent is decided in PHP, never by the model. That is the whole point:
 * the routing table decides which Laravel service owns the question, so there
 * is no path by which an unrecognised request reaches the LLM first and gets
 * interpreted there.
 */
final class AiIntent
{
    public const PRODUCT_SEARCH = 'PRODUCT_SEARCH';

    public const PRODUCT_RECOMMENDATION = 'PRODUCT_RECOMMENDATION';

    public const CROP_ADVICE = 'CROP_ADVICE';

    public const FERTILIZER_ADVICE = 'FERTILIZER_ADVICE';

    public const COST_ESTIMATION = 'COST_ESTIMATION';

    public const QUOTATION = 'QUOTATION';

    public const GENERAL_FARMING = 'GENERAL_FARMING';

    public const SYSTEM_HELP = 'SYSTEM_HELP';

    public const CLARIFICATION = 'CLARIFICATION';

    public const GREETING = 'GREETING';

    /**
     * Crop discovery, as named in the specification's intent list and defined
     * separately from crop search: "What crops do you have?" is answered with
     * the crops actually in the directory, and nothing else.
     */
    public const CROP_DISCOVERY = 'CROP_DISCOVERY';

    /**
     * Crop search: a crop directory question that narrows to a category or a
     * name. Kept distinct from CROP_DISCOVERY so the two can be measured
     * separately - discovery is "what exists", search is "which of these".
     */
    public const CROP_SEARCH = 'CROP_SEARCH';

    /**
     * A crop chosen for a purpose: "what fruit should I plant?", "suggest the
     * most tasty fruit I can grow". Where taste and preference are involved the
     * answer names the supported choices rather than crowning a winner.
     */
    public const CROP_RECOMMENDATION = 'CROP_RECOMMENDATION';

    /**
     * A crop chosen by season: "what can I plant this October?". Answered from
     * the decision formulas that exist for the season the month falls in.
     */
    public const SEASONAL_RECOMMENDATION = 'SEASONAL_RECOMMENDATION';

    /**
     * How to grow something: "how do I plant corn?". Answered from approved
     * knowledge only, and explicitly not with rates or spacings the application
     * does not hold.
     */
    public const FARMING_ADVICE = 'FARMING_ADVICE';

    /**
     * A store owner's own business question: what farmers are quoting from
     * this shop, what is trending, what to restock. The data is the store's
     * own quotations, which only a store owner has.
     */
    public const STORE_DEMAND = 'STORE_DEMAND';

    public const UNSUPPORTED = 'UNSUPPORTED';

    /** @return array<int, string> */
    public static function all(): array
    {
        return [
            self::PRODUCT_SEARCH,
            self::PRODUCT_RECOMMENDATION,
            self::CROP_DISCOVERY,
            self::CROP_SEARCH,
            self::CROP_RECOMMENDATION,
            self::SEASONAL_RECOMMENDATION,
            self::FARMING_ADVICE,
            self::STORE_DEMAND,
            self::CROP_ADVICE,
            self::FERTILIZER_ADVICE,
            self::COST_ESTIMATION,
            self::QUOTATION,
            self::GENERAL_FARMING,
            self::GREETING,
            self::SYSTEM_HELP,
            self::CLARIFICATION,
            self::UNSUPPORTED,
        ];
    }

    /**
     * Intents whose answer is produced entirely by Laravel, with no model call
     * at all.
     *
     * Two groups qualify. A clarification question is a template, not a
     * conversation, and letting the model write one is how a clarification
     * turns into a guess. The approved-knowledge intents are answers to
     * questions the application already has a curated record for, and the
     * model's only possible contribution there is to add an agronomic detail
     * nobody approved — a spacing, a rate, a spray schedule. So it is not
     * called.
     *
     * That reasoning is why the crop and seasonal routes are here too. They were
     * added to stop the assistant refusing legitimate farming questions, and
     * routing them to the model would have reopened exactly the failure they
     * were added to fix: a crop list with a planting distance attached to it,
     * invented, in the voice of the association.
     *
     * A greeting is here for a different reason. It is a courtesy, and there is
     * nothing in it for a model to improve.
     *
     * The model is left with the work it is actually good at: explaining
     * catalog rows and rule-engine output, both of which it must then defend
     * against AiResponseValidator.
     */
    public static function isDeterministicOnly(string $intent): bool
    {
        return in_array($intent, [
            self::CLARIFICATION,
            // The refusal and the how-to answer are Laravel's own wording.
            // Both used to be left callable so the model could phrase them
            // conversationally; that cost a network call per turn, and the
            // model's version of a refusal was the "I can't answer that"
            // sentence users complained about. The fixed answers below are
            // role-aware and still friendly.
            self::UNSUPPORTED,
            self::FARMING_ADVICE,
            self::CROP_ADVICE,
            self::CROP_DISCOVERY,
            self::CROP_SEARCH,
            self::CROP_RECOMMENDATION,
            self::SEASONAL_RECOMMENDATION,
            self::FERTILIZER_ADVICE,
            self::GREETING,
            self::QUOTATION,
        ], true);
    }
}
