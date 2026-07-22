<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * SeasonHelper — Determines the current farming season based on the
 * Philippine climate calendar (Bicol Region / Bulan, Sorsogon context).
 * 
 * Wet Season: June to October (Tag-ulan)
 * Dry Season: November to May (Tag-init / Tag-araw)
 * 
 * Also provides transitional periods and basic climate descriptions.
 */
class SeasonHelper
{
    /**
     * Determine the current season based on the month.
     * 
     * @param Carbon|null $date Optional date (defaults to now)
     * @return string 'wet' | 'dry'
     */
    public static function getCurrentSeason(?Carbon $date = null): string
    {
        $month = ($date ?? Carbon::now())->month;

        // Wet Season: June (6) to October (10)
        if ($month >= 6 && $month <= 10) {
            return 'wet';
        }

        // Dry Season: November (11) to May (5)
        return 'dry';
    }

    /**
     * Get the human-readable label for the current season.
     *
     * @param Carbon|null $date
     * @return string
     */
    public static function getSeasonLabel(?Carbon $date = null): string
    {
        return self::getCurrentSeason($date) === 'wet' ? 'Wet Season' : 'Dry Season';
    }

    /**
     * Get a detailed climate descriptor for the current period.
     *
     * @param Carbon|null $date
     * @return array{season: string, label: string, months: string, typical_weather: string, icon: string}
     */
    public static function getClimateInfo(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::now();
        $month = $date->month;
        $season = self::getCurrentSeason($date);

        if ($season === 'wet') {
            return [
                'season'          => 'wet',
                'label'           => 'Wet Season',
                'months'          => 'June – October',
                'typical_weather' => 'Heavy rainfall, high humidity, typhoon-prone. Ideal for flood-tolerant rice varieties.',
                'icon'            => 'cloud-rain',
                'temperature'     => '24°C – 32°C',
                'humidity'        => '80% – 90%',
                'recommendation'  => 'Use flood-tolerant rice varieties. Ensure proper drainage systems.',
            ];
        }

        // Check for dry season sub-periods
        $subSeason = match(true) {
            $month >= 11 && $month <= 1 => 'Cool Dry (Amihan)',
            $month >= 2 && $month <= 4 => 'Hot Dry (Tag-init)',
            $month == 5                => 'Transition to Wet',
            default                    => 'Dry Season',
        };

        $weatherDesc = match(true) {
            $month >= 11 && $month <= 1 => 'Cool northeast monsoon winds, minimal rainfall. Good for dry-season cropping.',
            $month >= 2 && $month <= 4 => 'High temperatures, very low rainfall. Requires irrigation for crops.',
            $month == 5                => 'End of dry season, increasing humidity, sporadic pre-monsoon rains.',
            default                    => 'Dry weather conditions.',
        };

        return [
            'season'          => 'dry',
            'label'           => 'Dry Season',
            'months'          => 'November – May',
            'sub_season'      => $subSeason,
            'typical_weather' => $weatherDesc,
            'icon'            => 'sun',
            'temperature'     => match(true) {
                $month >= 11 && $month <= 1 => '22°C – 28°C',
                $month >= 2 && $month <= 4 => '28°C – 36°C',
                default                    => '25°C – 33°C',
            },
            'humidity'        => match(true) {
                $month >= 11 && $month <= 1 => '70% – 80%',
                $month >= 2 && $month <= 4 => '60% – 75%',
                default                    => '65% – 80%',
            },
            'recommendation'  => match(true) {
                $month >= 11 && $month <= 1 => 'Ideal for rice and vegetable cropping with supplemental irrigation.',
                $month >= 2 && $month <= 4 => 'Drought-resistant varieties recommended. Ensure adequate irrigation.',
                $month == 5                => 'Prepare for wet season planting. Begin land preparation.',
                default                    => 'Good conditions for dry-season farming with irrigation support.',
            },
        ];
    }

    /**
     * Get the CSS class for the season badge/indicator.
     *
     * @param string $season 'wet' | 'dry'
     * @return string
     */
    public static function getSeasonBadgeClass(string $season): string
    {
        return $season === 'wet'
            ? 'background: #e0f2fe; color: #0284c7;'
            : 'background: #fef3c7; color: #d97706;';
    }

    /**
     * Check if the given season matches the current season.
     *
     * @param string $season 'wet' | 'dry'
     * @param Carbon|null $date
     * @return bool
     */
    public static function isCurrentSeason(string $season, ?Carbon $date = null): bool
    {
        return self::getCurrentSeason($date) === $season;
    }

    /**
     * Get month range for a season.
     *
     * @param string $season 'wet' | 'dry'
     * @return string
     */
    public static function getSeasonMonths(string $season): string
    {
        return $season === 'wet' ? 'June to October' : 'November to May';
    }
}

