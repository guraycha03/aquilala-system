<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * SeasonHelper — the application's climate calendar.
 *
 * The wet/dry boundary is admin-editable from Admin → Settings → Season
 * Settings and is stored through SettingsHelper, so a change there reaches
 * every consumer in one place: the sidebar climate card, farmer planting,
 * quotation requests, harvest reports, recommendations and the AI assistant
 * all ask this class which season it is.
 *
 * Defaults follow PAGASA: wet season June 1 – November 30 (Habagat),
 * dry season December 1 – May 31 (Amihan / tag-init). Only the wet window
 * is stored; the dry window is its complement, which keeps the two seasons
 * from ever overlapping or leaving a gap.
 */
class SeasonHelper
{
    /** Settings keys (system_settings table, through SettingsHelper). */
    public const SETTING_WET_START = 'season_wet_start_month';
    public const SETTING_WET_END = 'season_wet_end_month';
    public const SETTING_ACTIVE = 'season_active';
    public const SETTING_RECO = 'season_reco';

    /** PAGASA defaults: wet June (6) – November (11). */
    public const DEFAULT_WET_START = 6;
    public const DEFAULT_WET_END = 11;

    private const MONTH_NAMES = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    private const MONTH_ABBR = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];

    /**
     * The stored wet window as [start_month, end_month], 1–12.
     *
     * Reads defensively: before the first migration (or if the settings table
     * is unavailable) the PAGASA defaults apply instead of failing the page.
     *
     * @return array{0: int, 1: int}
     */
    public static function wetWindow(): array
    {
        return [
            self::readMonth(self::SETTING_WET_START, self::DEFAULT_WET_START),
            self::readMonth(self::SETTING_WET_END, self::DEFAULT_WET_END),
        ];
    }

    /**
     * The dry window — always the complement of the wet window.
     *
     * @return array{0: int, 1: int}
     */
    public static function dryWindow(): array
    {
        [$wetStart, $wetEnd] = self::wetWindow();

        return [$wetEnd % 12 + 1, $wetStart === 1 ? 12 : $wetStart - 1];
    }

    /**
     * Admin override of the active season: 'auto' (follow the calendar),
     * 'wet' or 'dry' (force one season everywhere until changed back).
     */
    public static function activeSeasonOverride(): string
    {
        try {
            $value = (string) SettingsHelper::get(self::SETTING_ACTIVE, 'auto');
        } catch (\Throwable) {
            return 'auto';
        }

        return in_array($value, ['auto', 'wet', 'dry'], true) ? $value : 'auto';
    }

    /**
     * Whether the admin asks the system to recommend seasonal varieties
     * automatically (stored from the Season Settings page).
     */
    public static function recommendsSeasonally(): bool
    {
        try {
            $value = SettingsHelper::get(self::SETTING_RECO, '1');
        } catch (\Throwable) {
            return true;
        }

        return in_array((string) $value, ['1', 'true'], true);
    }

    /**
     * Determine the season for a date.
     *
     * @param Carbon|null $date Optional date (defaults to now)
     * @return string 'wet' | 'dry'
     */
    public static function getCurrentSeason(?Carbon $date = null): string
    {
        $override = self::activeSeasonOverride();

        if ($override !== 'auto') {
            return $override;
        }

        $month = ($date ?? Carbon::now())->month;
        [$start, $end] = self::wetWindow();

        return self::monthInWindow($month, $start, $end) ? 'wet' : 'dry';
    }

    /**
     * Get the human-readable label for the current season.
     */
    public static function getSeasonLabel(?Carbon $date = null): string
    {
        return self::getCurrentSeason($date) === 'wet' ? 'Wet Season' : 'Dry Season';
    }

    /**
     * Month range of a season as the admin configured it:
     * "June – November" — or the compact "Jun–Nov" for tight UI spots
     * (card subs, option labels), matching the wording the app used
     * before the windows became configurable.
     */
    public static function rangeLabel(string $season, bool $short = false): string
    {
        [$start, $end] = $season === 'dry' ? self::dryWindow() : self::wetWindow();
        $names = $short ? self::MONTH_ABBR : self::MONTH_NAMES;

        return $names[$start] . ($short ? '–' : ' – ') . $names[$end];
    }

    /**
     * Get a detailed climate descriptor for the current period.
     *
     * @return array{season: string, label: string, months: string, typical_weather: string, icon: string}
     */
    public static function getClimateInfo(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::now();
        $month = $date->month;
        $season = self::getCurrentSeason($date);

        if ($season === 'wet') {
            // Within the wet season: the Habagat typhoon core runs Jun–Oct,
            // while Nov–Jan is the cooler, still-rainy Amihan.
            $subSeason = $month >= 6 && $month <= 10 ? 'Heavy Rain (Habagat)' : 'Cool Rainy (Amihan)';

            return [
                'season'          => 'wet',
                'label'           => 'Wet Season',
                'months'          => self::rangeLabel('wet'),
                'sub_season'      => $subSeason,
                'typical_weather' => $month >= 6 && $month <= 10
                    ? 'Strong southwest monsoon (Habagat) with heavy rainfall and typhoons — the main cropping window.'
                    : 'Northeast monsoon (Amihan) — still wet, but cooler and calmer. Good for a second crop.',
                'icon'            => 'cloud-rain',
                'temperature'     => $month >= 6 && $month <= 10 ? '25°C – 32°C' : '22°C – 28°C',
                'humidity'        => $month >= 6 && $month <= 10 ? '80% – 90%' : '75% – 88%',
                'recommendation'  => $month >= 6 && $month <= 10
                    ? 'Use flood-tolerant rice varieties. Ensure proper drainage systems.'
                    : 'Second-cropping window — plant short-maturing, rain-fed varieties before the dry season.',
            ];
        }

        // Dry season sub-periods (Feb–May)
        $subSeason = match (true) {
            $month >= 2 && $month <= 4 => 'Hot Dry (Tag-init)',
            $month == 5                => 'Transition to Wet',
            default                    => 'Dry Season',
        };

        $weatherDesc = match (true) {
            $month >= 2 && $month <= 4 => 'High temperatures, very low rainfall. Requires irrigation for crops.',
            $month == 5                => 'End of dry season, increasing humidity, sporadic pre-monsoon rains.',
            default                    => 'Dry weather conditions.',
        };

        return [
            'season'          => 'dry',
            'label'           => 'Dry Season',
            'months'          => self::rangeLabel('dry'),
            'sub_season'      => $subSeason,
            'typical_weather' => $weatherDesc,
            'icon'            => 'sun',
            'temperature'     => $month >= 2 && $month <= 4 ? '28°C – 36°C' : '25°C – 33°C',
            'humidity'        => $month >= 2 && $month <= 4 ? '60% – 75%' : '65% – 80%',
            'recommendation'  => match (true) {
                $month >= 2 && $month <= 4 => 'Drought-resistant varieties recommended. Ensure adequate irrigation.',
                $month == 5                => 'Prepare for wet-season planting. Begin land preparation.',
                default                    => 'Good conditions for dry-season farming with irrigation support.',
            },
        ];
    }

    /**
     * Get the CSS class for the season badge/indicator.
     */
    public static function getSeasonBadgeClass(string $season): string
    {
        return $season === 'wet'
            ? 'background: #e0f2fe; color: #0284c7;'
            : 'background: #fef3c7; color: #d97706;';
    }

    /**
     * Check if the given season matches the current season.
     */
    public static function isCurrentSeason(string $season, ?Carbon $date = null): bool
    {
        return self::getCurrentSeason($date) === $season;
    }

    /**
     * Get month range for a season as prose, e.g. "June to November".
     */
    public static function getSeasonMonths(string $season): string
    {
        [$start, $end] = $season === 'dry' ? self::dryWindow() : self::wetWindow();

        return self::MONTH_NAMES[$start] . ' to ' . self::MONTH_NAMES[$end];
    }

    /**
     * Month number inside a possibly year-wrapping window (Jun–Jan style).
     */
    private static function monthInWindow(int $month, int $start, int $end): bool
    {
        return $start <= $end
            ? $month >= $start && $month <= $end
            : $month >= $start || $month <= $end;
    }

    /**
     * Read a stored month, falling back to the default when unset, invalid,
     * or when the settings table does not exist yet.
     */
    private static function readMonth(string $key, int $default): int
    {
        try {
            $month = (int) SettingsHelper::get($key);
        } catch (\Throwable) {
            return $default;
        }

        return $month >= 1 && $month <= 12 ? $month : $default;
    }
}
