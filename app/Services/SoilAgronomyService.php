<?php

namespace App\Services;

use Carbon\Carbon;
use App\Helpers\SeasonHelper;

/**
 * SoilAgronomyService
 * 
 * Provides simulated soil and agronomy indicator data for the Aquilala platform.
 * Estimates soil moisture levels, soil surface temperature, evapotranspiration rates,
 * and field readiness metrics based on the current season and soil type context.
 * 
 * Soil Types:
 * - clay_loam — Standard water retention
 * - sandy — High drainage, low water retention
 * - peat — Organic, nutrient-rich but acidic
 */
class SoilAgronomyService
{
    /**
     * Soil moisture reference ranges by soil type (% volumetric water content)
     */
    const SOIL_MOISTURE_RANGES = [
        'clay_loam' => [
            'field_capacity' => 75,    // % — optimal upper limit
            'wilting_point'  => 25,    // % — critical lower limit
            'wet_season'     => ['min' => 55, 'max' => 85],
            'dry_season'     => ['min' => 20, 'max' => 50],
        ],
        'sandy' => [
            'field_capacity' => 50,
            'wilting_point'  => 10,
            'wet_season'     => ['min' => 25, 'max' => 55],
            'dry_season'     => ['min' => 5,  'max' => 30],
        ],
        'peat' => [
            'field_capacity' => 85,
            'wilting_point'  => 35,
            'wet_season'     => ['min' => 60, 'max' => 95],
            'dry_season'     => ['min' => 30, 'max' => 65],
        ],
    ];

    /**
     * Evapotranspiration ranges by season (mm/day)
     */
    const ET_RANGES = [
        'wet' => ['min' => 2.5, 'max' => 4.5],
        'dry' => ['min' => 4.0, 'max' => 7.5],
    ];

    /**
     * Soil surface temperature adjustment from ambient
     */
    const SOIL_TEMP_ADJUSTMENT = [
        'clay_loam' => ['day_offset' => 2, 'night_offset' => -1],
        'sandy'     => ['day_offset' => 5, 'night_offset' => -2],
        'peat'      => ['day_offset' => 1, 'night_offset' => 0],
    ];

    /**
     * Get comprehensive soil and agronomy indicators.
     *
     * @param string $soilType 'clay_loam' | 'sandy' | 'peat'
     * @return array
     */
    public static function getSoilIndicators(string $soilType = 'clay_loam'): array
    {
        $season = SeasonHelper::getCurrentSeason();
        $now = Carbon::now();
        $hourOfDay = $now->hour;
        $isDaytime = $hourOfDay >= 6 && $hourOfDay <= 18;

        // Get season-adjusted ambient temperature from WeatherSimulationService
        $weather = WeatherSimulationService::getCurrentWeather();
        $ambientTemp = $weather['temperature'];

        // ─── Soil Moisture ─────────────────────────────────────────
        $moistureRanges = self::SOIL_MOISTURE_RANGES[$soilType];
        $seasonRange = $season === 'wet' ? $moistureRanges['wet_season'] : $moistureRanges['dry_season'];
        
        // Current moisture with some variation
        $moisture = rand($seasonRange['min'], $seasonRange['max']);
        
        // Apply time-of-day effect (slightly drier in afternoon due to evaporation)
        if ($hourOfDay >= 11 && $hourOfDay <= 15) {
            $moisture = max($seasonRange['min'], $moisture - rand(2, 5));
        }

        // Determine soil moisture status
        $moistureStatus = self::getSoilMoistureStatus($moisture, $moistureRanges);

        // ─── Soil Surface Temperature ──────────────────────────────
        $tempAdjustment = self::SOIL_TEMP_ADJUSTMENT[$soilType];
        $offset = $isDaytime ? $tempAdjustment['day_offset'] : $tempAdjustment['night_offset'];
        $soilTemp = round($ambientTemp + $offset + (rand(-10, 10) / 10), 1);

        // ─── Evapotranspiration (ET) ───────────────────────────────
        $etRange = self::ET_RANGES[$season];
        $baseET = round(($etRange['min'] + $etRange['max']) / 2 + rand(-50, 50) / 100, 2);
        
        // Adjust ET based on current conditions
        if ($isDaytime && $hourOfDay >= 10 && $hourOfDay <= 15) {
            $baseET = round(min($etRange['max'], $baseET * 1.3), 2); // Peak ET at midday
        }
        if ($moisture < $moistureRanges['wilting_point']) {
            $baseET = round($baseET * 0.5, 2); // Reduced ET when soil is very dry
        }

        // ET status
        $etStatus = self::getETStatus($baseET, $season);

        // ─── Field Readiness Score ─────────────────────────────────
        $fieldReadiness = self::calculateFieldReadiness($moisture, $soilTemp, $moistureRanges, $season);

        return [
            'soil_type'           => $soilType,
            'soil_type_label'     => CostQuotationEngine::getSoilTypeLabel($soilType),
            'moisture'            => $moisture,
            'moisture_percent'    => $moisture,
            'moisture_status'     => $moistureStatus,
            'field_capacity'      => $moistureRanges['field_capacity'],
            'wilting_point'       => $moistureRanges['wilting_point'],
            'surface_temperature' => $soilTemp,
            'surface_temp_unit'   => '°C',
            'evapotranspiration'  => $baseET,
            'et_unit'             => 'mm/day',
            'et_status'           => $etStatus,
            'field_readiness'     => $fieldReadiness,
            'recommendation'      => self::getRecommendation($soilType, $season, $moistureStatus, $fieldReadiness),
            'updated_at'          => $now->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get soil moisture status label and color.
     */
    private static function getSoilMoistureStatus(int $moisture, array $ranges): array
    {
        if ($moisture >= $ranges['field_capacity']) {
            return [
                'label' => 'Saturated',
                'color' => '#0284c7',
                'bg'    => '#e0f2fe',
                'icon'  => 'droplets',
            ];
        }
        if ($moisture >= $ranges['field_capacity'] * 0.7) {
            return [
                'label' => 'Optimal',
                'color' => '#16a34a',
                'bg'    => '#f0fdf4',
                'icon'  => 'droplet',
            ];
        }
        if ($moisture >= $ranges['wilting_point'] * 1.3) {
            return [
                'label' => 'Adequate',
                'color' => '#d97706',
                'bg'    => '#fef3c7',
                'icon'  => 'droplet',
            ];
        }
        if ($moisture >= $ranges['wilting_point']) {
            return [
                'label' => 'Low',
                'color' => '#f97316',
                'bg'    => '#fff7ed',
                'icon'  => 'alert-triangle',
            ];
        }
        return [
            'label' => 'Critical',
            'color' => '#dc2626',
            'bg'    => '#fef2f2',
            'icon'  => 'alert-triangle',
        ];
    }

    /**
     * Get evapotranspiration status.
     */
    private static function getETStatus(float $et, string $season): array
    {
        $etRange = self::ET_RANGES[$season];
        $midPoint = ($etRange['min'] + $etRange['max']) / 2;

        if ($et > $etRange['max'] * 0.9) {
            return [
                'label' => 'Very High',
                'color' => '#dc2626',
                'bg'    => '#fef2f2',
            ];
        }
        if ($et > $midPoint) {
            return [
                'label' => 'High',
                'color' => '#d97706',
                'bg'    => '#fef3c7',
            ];
        }
        if ($et < $etRange['min'] * 1.1) {
            return [
                'label' => 'Low',
                'color' => '#0284c7',
                'bg'    => '#e0f2fe',
            ];
        }
        return [
            'label' => 'Moderate',
            'color' => '#16a34a',
            'bg'    => '#f0fdf4',
        ];
    }

    /**
     * Calculate field readiness score (0–100).
     */
    private static function calculateFieldReadiness(int $moisture, float $soilTemp, array $ranges, string $season): array
    {
        $score = 100;

        // Moisture penalty
        if ($moisture > $ranges['field_capacity']) {
            $score -= ($moisture - $ranges['field_capacity']) * 2; // Too wet
        } elseif ($moisture < $ranges['wilting_point'] * 1.5) {
            $score -= ($ranges['wilting_point'] * 1.5 - $moisture) * 2; // Too dry
        }

        // Temperature penalty (soil too hot or too cold)
        if ($soilTemp > 38) {
            $score -= ($soilTemp - 38) * 3;
        } elseif ($soilTemp < 15) {
            $score -= (15 - $soilTemp) * 2;
        }

        // Season adjustment
        if ($season === 'wet' && $moisture > $ranges['field_capacity'] * 0.8) {
            $score -= 10; // Wet season + high moisture = reduced readiness
        }

        $score = max(0, min(100, $score));

        $label = $score >= 80 ? 'Ready' : ($score >= 50 ? 'Conditional' : 'Not Ready');
        $color = $score >= 80 ? '#16a34a' : ($score >= 50 ? '#d97706' : '#dc2626');

        return [
            'score' => $score,
            'label' => $label,
            'color' => $color,
        ];
    }

    /**
     * Generate actionable recommendation based on soil conditions.
     */
    private static function getRecommendation(string $soilType, string $season, array $moistureStatus, array $fieldReadiness): string
    {
        $recs = [];

        if ($fieldReadiness['score'] >= 80) {
            $recs[] = 'Field conditions are optimal for planting.';
        } elseif ($fieldReadiness['score'] >= 50) {
            $recs[] = 'Proceed with caution. Monitor soil moisture regularly.';
        } else {
            $recs[] = 'Delay field operations. Conditions are not favorable.';
        }

        if ($moistureStatus['label'] === 'Saturated') {
            $recs[] = 'Improve drainage or wait for excess water to subside.';
        } elseif ($moistureStatus['label'] === 'Critical' || $moistureStatus['label'] === 'Low') {
            $recs[] = 'Irrigate immediately to prevent crop stress.';
        }

        if ($soilType === 'sandy' && $season === 'dry') {
            $recs[] = 'Apply mulch to reduce evaporation. Consider drip irrigation.';
        } elseif ($soilType === 'peat') {
            $recs[] = 'Monitor pH levels. Lime application may be needed.';
        }

        return implode(' ', $recs);
    }

    /**
     * Get soil type recommendation for the rule engine context.
     */
    public static function getSoilTypeForEngine(): string
    {
        // Default to clay_loam; in production, this would come from the farmer's profile
        return 'clay_loam';
    }
}

