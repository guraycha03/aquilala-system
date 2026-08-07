<?php

namespace App\Services;

use Carbon\Carbon;
use App\Helpers\SeasonHelper;

/**
 * WeatherSimulationService
 * 
 * Generates realistic simulated weather data for the Aquilala platform
 * based on the Philippine climate calendar (Bicol Region context).
 * Provides hourly forecasts, severe weather alerts, and seasonal patterns
 * to power interactive weather widgets across all dashboards.
 */
class WeatherSimulationService
{
    /**
     * Temperature ranges by season (°C)
     */
    const TEMP_RANGES = [
        'wet' => ['min' => 24, 'max' => 32],
        'dry' => ['min' => 22, 'max' => 36],
    ];

    /**
     * Humidity ranges by season (%)
     */
    const HUMIDITY_RANGES = [
        'wet' => ['min' => 78, 'max' => 92],
        'dry' => ['min' => 58, 'max' => 82],
    ];

    /**
     * Wind speed ranges by season (km/h)
     */
    const WIND_RANGES = [
        'wet' => ['min' => 10, 'max' => 45],
        'dry' => ['min' => 5, 'max' => 25],
    ];

    /**
     * UV Index ranges by season
     */
    const UV_RANGES = [
        'wet' => ['min' => 3, 'max' => 8],
        'dry' => ['min' => 6, 'max' => 12],
    ];

    /**
     * Rain probability by season (%)
     */
    const RAIN_PROBABILITY = [
        'wet' => ['min' => 40, 'max' => 95],
        'dry' => ['min' => 5, 'max' => 40],
    ];

    /**
     * Generate a 24-hour hourly weather forecast.
     *
     * @return array
     */
    public static function getHourlyForecast(): array
    {
        $season = SeasonHelper::getCurrentSeason();
        $now = Carbon::now();
        $hours = [];

        for ($i = 0; $i < 24; $i++) {
            $hourTime = $now->copy()->addHours($i);
            $hourOfDay = $hourTime->hour;

            // Temperature varies by time of day: cooler at night, peak at 1-3pm
            $baseTemp = rand(self::TEMP_RANGES[$season]['min'], self::TEMP_RANGES[$season]['max']);
            if ($hourOfDay >= 0 && $hourOfDay <= 5) {
                $temp = $baseTemp - rand(3, 6); // Night cooling
            } elseif ($hourOfDay >= 11 && $hourOfDay <= 15) {
                $temp = $baseTemp + rand(1, 3); // Peak heat
            } else {
                $temp = $baseTemp;
            }

            // Rain probability higher during wet season afternoons
            $baseRainProb = rand(self::RAIN_PROBABILITY[$season]['min'], self::RAIN_PROBABILITY[$season]['max']);
            if ($season === 'wet' && $hourOfDay >= 13 && $hourOfDay <= 18) {
                $rainProb = min(98, $baseRainProb + rand(10, 25));
            } else {
                $rainProb = $baseRainProb;
            }

            // Wind picks up in wet season afternoons
            $baseWind = rand(self::WIND_RANGES[$season]['min'], self::WIND_RANGES[$season]['max']);
            if ($season === 'wet' && $hourOfDay >= 12 && $hourOfDay <= 18) {
                $wind = $baseWind + rand(5, 15);
            } else {
                $wind = $baseWind;
            }

            // Humidity higher during wet season and at night/morning
            $baseHumidity = rand(self::HUMIDITY_RANGES[$season]['min'], self::HUMIDITY_RANGES[$season]['max']);
            if ($hourOfDay >= 0 && $hourOfDay <= 8) {
                $humidity = min(98, $baseHumidity + rand(3, 8));
            } else {
                $humidity = $baseHumidity;
            }

            // UV peaks midday
            $baseUv = rand(self::UV_RANGES[$season]['min'], self::UV_RANGES[$season]['max']);
            if ($hourOfDay >= 10 && $hourOfDay <= 14) {
                $uv = min(14, $baseUv + rand(1, 3));
            } elseif ($hourOfDay >= 6 && $hourOfDay <= 17) {
                $uv = $baseUv;
            } else {
                $uv = 0; // Night
            }

            $hours[] = [
                'time'          => $hourTime->format('H:i'),
                'hour'          => $hourOfDay,
                'temperature'   => round($temp, 1),
                'humidity'      => $humidity,
                'wind_speed'    => round($wind, 1),
                'uv_index'      => $uv,
                'rain_probability' => $rainProb,
                'condition'     => self::getConditionLabel($season, $rainProb, $hourOfDay),
                'icon'          => self::getConditionIcon($season, $rainProb, $hourOfDay),
            ];
        }

        return $hours;
    }

    /**
     * Get current weather conditions summary.
     *
     * @return array
     */
    public static function getCurrentWeather(): array
    {
        $season = SeasonHelper::getCurrentSeason();
        $now = Carbon::now();
        $hourOfDay = $now->hour;

        $temp = rand(self::TEMP_RANGES[$season]['min'], self::TEMP_RANGES[$season]['max']);
        $humidity = rand(self::HUMIDITY_RANGES[$season]['min'], self::HUMIDITY_RANGES[$season]['max']);
        $wind = rand(self::WIND_RANGES[$season]['min'], self::WIND_RANGES[$season]['max']);
        $uv = ($hourOfDay >= 6 && $hourOfDay <= 17) 
            ? rand(self::UV_RANGES[$season]['min'], min(14, self::UV_RANGES[$season]['max'] + 2))
            : 0;
        $rainProb = rand(self::RAIN_PROBABILITY[$season]['min'], self::RAIN_PROBABILITY[$season]['max']);

        // Calculate "feels like" temp
        $feelsLike = $temp;
        if ($humidity > 80) {
            $feelsLike += round(($humidity - 80) * 0.1, 1); // Humidex effect
        }
        if ($wind > 30) {
            $feelsLike -= round($wind * 0.05, 1); // Wind chill
        }

        return [
            'temperature'       => round($temp, 1),
            'feels_like'        => round($feelsLike, 1),
            'humidity'          => $humidity,
            'wind_speed'        => round($wind, 1),
            'uv_index'          => $uv,
            'rain_probability'  => $rainProb,
            'condition'         => self::getConditionLabel($season, $rainProb, $hourOfDay),
            'icon'              => self::getConditionIcon($season, $rainProb, $hourOfDay),
            'season'            => $season,
            'updated_at'        => $now->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Generate severe weather alerts based on season context.
     *
     * @return array
     */
    public static function getWeatherAlerts(): array
    {
        $season = SeasonHelper::getCurrentSeason();
        $alerts = [];

        if ($season === 'wet') {
            // Wet season alerts — typhoon/heavy rain warnings
            if (rand(1, 3) > 1) { // 66% chance of active alert
                $alerts[] = [
                    'type'        => 'warning',
                    'severity'    => 'moderate',
                    'title'       => 'Heavy Rainfall Advisory',
                    'message'     => 'Expect 15–25mm rainfall over the next 48 hours. Monitor low-lying field areas for potential flooding.',
                    'action_tip'  => 'Check drainage systems. Delay fertilizer application until rain subsides.',
                    'icon'        => 'cloud-rain',
                    'color'       => '#d97706',
                    'bg_color'    => '#fef3c7',
                ];
            }
            if (rand(1, 4) === 1) { // 25% chance of typhoon alert
                $alerts[] = [
                    'type'        => 'danger',
                    'severity'    => 'severe',
                    'title'       => '🌀 Typhoon Watch — Signal #1',
                    'message'     => 'A low-pressure area east of Bicol Region may develop into a tropical depression within 48 hours.',
                    'action_tip'  => 'Secure farm equipment. Prepare flood buffers. Delay harvesting by 2–3 days.',
                    'icon'        => 'alert-triangle',
                    'color'       => '#dc2626',
                    'bg_color'    => '#fef2f2',
                ];
            }
        } else {
            // Dry season alerts — drought/heat warnings
            if (rand(1, 3) > 1) { // 66% chance
                $alerts[] = [
                    'type'        => 'warning',
                    'severity'    => 'moderate',
                    'title'       => 'High Temperature Advisory',
                    'message'     => 'Temperatures expected to reach 34–36°C this week. Increased irrigation demand expected.',
                    'action_tip'  => 'Irrigate early morning or evening to reduce water loss from evaporation. Monitor soil moisture daily.',
                    'icon'        => 'sun',
                    'color'       => '#d97706',
                    'bg_color'    => '#fef3c7',
                ];
            }
            if (rand(1, 5) === 1) { // 20% chance of El Niño advisory
                $alerts[] = [
                    'type'        => 'danger',
                    'severity'    => 'severe',
                    'title'       => '🌤️ El Niño Drought Advisory',
                    'message'     => 'Below-normal rainfall predicted for the next 2 months. Water conservation measures strongly advised.',
                    'action_tip'  => 'Plant drought-resistant crop varieties. Implement water-saving irrigation (drip/sprinkler). Consider adjusting planting schedule.',
                    'icon'        => 'alert-triangle',
                    'color'       => '#dc2626',
                    'bg_color'    => '#fef2f2',
                ];
            }
        }

        // If no alerts triggered, return a "conditions normal" info card
        if (empty($alerts)) {
            $alerts[] = [
                'type'        => 'success',
                'severity'    => 'normal',
                'title'       => $season === 'wet' ? 'Normal Wet Season Conditions' : 'Normal Dry Season Conditions',
                'message'     => 'No severe weather events forecast for your area in the coming days.',
                'action_tip'  => $season === 'wet' 
                    ? 'Standard field monitoring recommended. Good conditions for ongoing crop maintenance.'
                    : 'Optimal conditions for field preparation and planting with irrigation support.',
                'icon'        => 'check-circle',
                'color'       => '#16a34a',
                'bg_color'    => '#f0fdf4',
            ];
        }

        return $alerts;
    }

    /**
     * Get weather context code for the rule-based engine.
     *
     * @return string
     */
    public static function getWeatherContextForEngine(): string
    {
        $season = SeasonHelper::getCurrentSeason();
        $alerts = self::getWeatherAlerts();
        
        $hasSevere = collect($alerts)->contains('severity', 'severe');
        $hasWarning = collect($alerts)->contains('severity', 'moderate');

        if ($hasSevere && $season === 'wet') return 'typhoon';
        if ($hasSevere && $season === 'dry') return 'el_nino';
        if ($hasWarning && $season === 'wet') return 'low_rain';
        return 'normal';
    }

    /**
     * Get a human-readable condition label.
     */
    private static function getConditionLabel(string $season, int $rainProb, int $hour): string
    {
        if ($hour < 6 || $hour > 18) {
            return $rainProb > 60 ? 'Rain Likely' : 'Partly Cloudy';
        }
        if ($rainProb > 80) return 'Heavy Rain';
        if ($rainProb > 50) return 'Rain Showers';
        if ($season === 'dry' && $rainProb < 15) return 'Sunny';
        if ($season === 'wet' && $rainProb < 30) return 'Partly Cloudy';
        return 'Cloudy';
    }

    /**
     * Get a weather icon name.
     */
    private static function getConditionIcon(string $season, int $rainProb, int $hour): string
    {
        if ($hour < 6 || $hour > 18) {
            return $rainProb > 60 ? 'cloud-rain' : 'moon';
        }
        if ($rainProb > 80) return 'cloud-heavy-rain';
        if ($rainProb > 50) return 'cloud-rain';
        if ($season === 'dry' && $rainProb < 15) return 'sun';
        return 'cloud-sun';
    }
}

