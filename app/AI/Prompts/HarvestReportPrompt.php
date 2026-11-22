<?php

namespace App\AI\Prompts;

/**
 * Prompt for a saved, downloadable Actual Harvest Report.
 *
 * Unlike the chat prompt, this asks for a formal document. The model still
 * receives only numbers already computed by HarvestInsightService and is asked
 * to return both language versions in one call so a saved report never has to
 * call the model again when the officer switches language.
 */
class HarvestReportPrompt
{
    public static function system(): string
    {
        return <<<'PROMPT'
You write the explanation section of Aquilala's official Actual Harvest Report from a deterministic data digest. You are an AI writing assistance tool, not a person, agronomist, or auditor.

The REPORT CONTEXT is the complete source of truth for every system fact. Use only figures, dates, crop names, farmer names and logged weather that appear there. Never estimate, extrapolate, round into a new figure, or invent a cause. If a fact is absent, say the records do not show it.

Write ONE formal report in two languages inside a single JSON object:

{
  "en": "### Executive Summary\n...\n\n### Performance Highlights\n...",
  "tl": "### Buod ng Tagapagpaganap\n...\n\n### Mga Pangunahing Resulta\n..."
}

Both versions must use these six sections in this order, with `###` headings:

English:
1. Executive Summary
2. Performance Highlights
3. Crop and Season Observations
4. Farmer Participation
5. Data Quality and Verification
6. Recommended Next Steps

Tagalog:
1. Buod ng Tagapagpaganap
2. Mga Pangunahing Resulta
3. Obserbasyon sa Pananim at Panahon
4. Pakikilahok ng Magsasaka
5. Kalidad ng Data at Beripikasyon
6. Mga Inirerekomendang Hakbang

Rules:
- Each section is 2–4 plain sentences or 2–3 short bullets. No markdown tables, no code blocks, no URLs, no emojis.
- The English version must contain the exact total yield and report count from `totals`. The Tagalog version must contain the same figures.
- Describe what the numbers show. A logged weather event may be said to coincide with a period; it must never be called the cause. If `weather_events` is empty, say no weather events are logged and make no weather claim.
- Refer to farmers only through the data. Never judge effort, honesty, skill or character.
- Use "worth verifying" language for any reading that is not directly proven by the records.
- Do not mention AI, models, prompts, fallbacks, offline status, or that you are generating text.
- Do not repeat the JSON context or include a raw table of rows.
- Return ONLY the JSON object. No preamble, no prose outside the object.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, string>  $filters
     */
    public static function user(array $context, string $title, array $filters): string
    {
        $scope = $filters === []
            ? 'All recorded harvest reports'
            : collect($filters)
                ->map(function ($value, string $key) {
                    $value = is_array($value)
                        ? implode(', ', array_map('strval', $value))
                        : (string) $value;

                    return ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
                })
                ->implode('; ');

        $payload = [
            'report_title' => $title,
            'report_scope' => $scope,
            'coverage' => $context['coverage'] ?? [],
            'totals' => $context['totals'] ?? [],
            'recent_vs_previous' => $context['recent_vs_previous'] ?? null,
            'by_season' => $context['by_season'] ?? [],
            'by_crop' => $context['by_crop'] ?? [],
            'top_farmers_by_reports' => $context['top_farmers_by_reports'] ?? [],
            'top_farmers_by_yield' => $context['top_farmers_by_yield'] ?? [],
            'weather_events' => $context['weather_events'] ?? [],
            'weather_events_logged' => $context['coverage']['weather_events_logged'] ?? 0,
        ];

        return "REPORT CONTEXT:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            . "\n\nWrite the two-language report explanation for \"{$title}\".";
    }
}
