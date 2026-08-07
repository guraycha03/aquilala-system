<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Helpers\SeasonHelper;

class WeatherService
{
    const LATITUDE = 12.6700;
    const LONGITUDE = 123.8800;
    const LOCATION_NAME = 'Bulan, Sorsogon';
    const TIMEZONE = 'Asia/Manila';
    const COUNTRY = 'Philippines';

    /**
     * Cache duration in seconds (30 minutes).
     */
    const CACHE_DURATION = 1800;

    /**
     * Get the current weather for Bulan, Sorsogon.
     *
     * Uses OpenWeatherMap One Call API 3.0 with a 30-minute server-side cache
     * so the temperature stays consistent between page refreshes and the API
     * rate limit is respected. Falls back to a realistic, season-aware default
     * (NOT random) when the API is unavailable or no key is configured.
     *
     * @return array
     */
    public static function getCurrentWeather(): array
    {
        return Cache::remember('weather_bulan_sorsogon', self::CACHE_DURATION, function () {
            try {
                $apiKey = config('services.openweather.key');

                // No API key configured? Use the stable fallback (no network call).
                if (empty($apiKey)) {
                    return self::getFallbackWeather();
                }

                $response = Http::timeout(10)
                    ->get(config('services.openweather.url'), [
                        'lat' => self::LATITUDE,
                        'lon' => self::LONGITUDE,
                        'exclude' => 'minutely,daily,alerts',
                        'units' => 'metric',
                        'appid' => $apiKey,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $current = $data['current'] ?? [];

                    $weatherCode = $current['weather'][0]['id'] ?? 0;
                    $weatherMain = strtolower($current['weather'][0]['main'] ?? 'Clouds');

                    $weather = [
                        'temperature'   => round($current['temp'] ?? 28, 1),
                        'feels_like'    => round($current['feels_like'] ?? ($current['temp'] ?? 28), 1),
                        'humidity'      => $current['humidity'] ?? 78,
                        'wind_speed'    => round(($current['wind_speed'] ?? 10) * 3.6, 1), // m/s -> km/h
                        'uv_index'      => round($current['uvi'] ?? 5, 1),
                        'rain'          => ($current['rain']['1h'] ?? 0),
                        'precipitation' => ($current['rain']['1h'] ?? 0) + ($current['snow']['1h'] ?? 0),
                        'weather_code'  => $weatherCode,
                        'condition'     => self::getWeatherCondition($weatherCode, $weatherMain),
                        'icon'          => self::getWeatherIcon($weatherCode, $weatherMain),
                        'season'        => SeasonHelper::getCurrentSeason(),
                        'location'      => self::LOCATION_NAME,
                        'country'       => self::COUNTRY,
                        'latitude'      => self::LATITUDE,
                        'longitude'     => self::LONGITUDE,
                        'updated_at'    => now()->format('Y-m-d H:i:s'),
                        'is_real'       => true,
                        'is_real_data'  => true,
                    ];

                    return $weather;
                }
            } catch (\Exception $e) {
                // Network failure, invalid key, etc. — fall through to fallback.
            }

            return self::getFallbackWeather();
        });
    }

    /**
     * Get the hourly forecast for the next 24 hours.
     *
     * @return array
     */
    public static function getHourlyForecast(): array
    {
        return Cache::remember('weather_bulan_sorsogon_hourly', self::CACHE_DURATION, function () {
            try {
                $apiKey = config('services.openweather.key');

                if (empty($apiKey)) {
                    return self::getFallbackHourly();
                }

                $response = Http::timeout(10)
                    ->get(config('services.openweather.url'), [
                        'lat' => self::LATITUDE,
                        'lon' => self::LONGITUDE,
                        'exclude' => 'minutely,daily,alerts',
                        'units' => 'metric',
                        'appid' => $apiKey,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $hourly = $data['hourly'] ?? [];
                    $hours = [];

                    $now = now()->timezone(self::TIMEZONE);

                    foreach ($hourly as $i => $item) {
                        if (!is_array($item)) {
                            continue;
                        }

                        $dt = $item['dt'] ?? null;
                        if ($dt === null) {
                            continue;
                        }

                        $carbonTime = Carbon::createFromTimestamp($dt);

                        // Skip past hours.
                        if ($carbonTime->lt($now->copy()->subHour())) {
                            continue;
                        }

                        $weatherCode = $item['weather'][0]['id'] ?? 0;
                        $weatherMain = strtolower($item['weather'][0]['main'] ?? 'Clouds');
                        $tempVal = round($item['temp'] ?? 28, 1);
                        $rainVal = round($item['pop'] ?? 0 * 100, 0);

                        $hours[] = [
                            'time'              => $carbonTime->format('g A'),
                            'formatted_time'    => $carbonTime->format('H:i'),
                            'hour'              => $carbonTime->hour,
                            'temperature'       => $tempVal,
                            'rain_probability'  => $rainVal,
                            'temp'              => $tempVal,
                            'rain_prob'         => $rainVal,
                            'pop'               => $rainVal,
                            'humidity'          => $item['humidity'] ?? 78,
                            'wind_speed'        => round(($item['wind_speed'] ?? 10) * 3.6, 1),
                            'uv_index'          => round($item['uvi'] ?? 5, 1),
                            'weather_code'      => $weatherCode,
                            'condition'         => self::getWeatherCondition($weatherCode, $weatherMain),
                            'icon'              => self::getWeatherIcon($weatherCode, $weatherMain),
                        ];

                        if (count($hours) >= 24) {
                            break;
                        }
                    }

                    if (!empty($hours)) {
                        return $hours;
                    }
                }
            } catch (\Exception $e) {
                // Fall through to fallback.
            }

            return self::getFallbackHourly();
        });
    }

    /**
     * Generate weather alerts based on current conditions and forecast.
     *
     * @return array
     */
    public static function getWeatherAlerts(): array
    {
        $weather = self::getCurrentWeather();
        $hourly = self::getHourlyForecast();
        $alerts = [];

        if ($weather['temperature'] >= 35) {
            $alerts[] = [
                'type' => 'warning',
                'severity' => 'moderate',
                'title' => '🌡️ Extreme Heat Advisory',
                'message' => "Current temperature is {$weather['temperature']}°C. Prolonged exposure may cause heat stress to crops and livestock.",
                'action_tip' => 'Irrigate during early morning or evening. Provide shade for seedlings. Monitor for heat stress symptoms.',
                'icon' => 'alert-triangle',
                'color' => '#d97706',
                'bg_color' => '#fef3c7',
            ];
        }

        if ($weather['wind_speed'] >= 40) {
            $alerts[] = [
                'type' => 'warning',
                'severity' => 'moderate',
                'title' => '💨 Strong Wind Advisory',
                'message' => "Wind speeds reaching {$weather['wind_speed']} km/h. Potential damage to tall crops and structures.",
                'action_tip' => 'Secure lightweight structures. Delay spraying operations. Support tall crops if needed.',
                'icon' => 'wind',
                'color' => '#d97706',
                'bg_color' => '#fef3c7',
            ];
        }

        $maxRainProb = collect($hourly)->max('rain_probability') ?? 0;
        if ($maxRainProb >= 70) {
            $alerts[] = [
                'type' => 'warning',
                'severity' => 'moderate',
                'title' => '🌧️ Heavy Rain Expected',
                'message' => "Rain probability reaching {$maxRainProb}% in the next hours. Prepare for possible field flooding.",
                'action_tip' => 'Check drainage systems. Delay fertilizer/pesticide application. Monitor low-lying areas.',
                'icon' => 'cloud-rain',
                'color' => '#0284c7',
                'bg_color' => '#e0f2fe',
            ];
        }

        if ($weather['uv_index'] >= 8) {
            $alerts[] = [
                'type' => 'warning',
                'severity' => 'moderate',
                'title' => '☀️ Extreme UV Advisory',
                'message' => "UV Index at {$weather['uv_index']} — very high. Protect yourself and sensitive crops.",
                'action_tip' => 'Limit outdoor work between 10AM-4PM. Wear protective gear. Provide shade for seedlings.',
                'icon' => 'sun',
                'color' => '#f97316',
                'bg_color' => '#fff7ed',
            ];
        }

        if (empty($alerts)) {
            $alerts[] = [
                'type' => 'success',
                'severity' => 'normal',
                'title' => '✅ Normal Conditions',
                'message' => 'Current weather conditions are favorable for farming operations. Proceed with regular activities.',
                'action_tip' => 'Standard field monitoring recommended. Good conditions for planting, harvesting, and maintenance.',
                'icon' => 'check-circle',
                'color' => '#16a34a',
                'bg_color' => '#f0fdf4',
            ];
        }

        return $alerts;
    }

    /**
     * Get the weather context code for the rule-based quotation engine.
     *
     * @return string
     */
    public static function getWeatherContextForEngine(): string
    {
        $weather = self::getCurrentWeather();
        $hourly = self::getHourlyForecast();
        $season = SeasonHelper::getCurrentSeason();

        $maxRain = collect($hourly)->max('rain_probability') ?? 0;
        $maxTemp = collect($hourly)->max('temperature') ?? 0;
        $maxWind = collect($hourly)->max('wind_speed') ?? 0;

        if ($season === 'wet' && ($maxRain >= 80 || $maxWind >= 50)) {
            return 'typhoon';
        }
        if ($season === 'dry' && $maxTemp >= 35 && $maxRain < 20) {
            return 'el_nino';
        }
        if ($season === 'wet' && $maxRain < 30) {
            return 'low_rain';
        }
        return 'normal';
    }

    /**
     * Map an OpenWeatherMap weather condition code to a human-readable label.
     */
    private static function getWeatherCondition(int $code, string $main): string
    {
        return match (true) {
            $code === 800 => 'Clear Sky',
            $code === 801 => 'Partly Cloudy',
            $code === 802, $code === 803, $code === 804 => 'Cloudy',
            $code >= 500 && $code <= 531 => 'Rain',
            $code >= 200 && $code <= 232 => 'Thunderstorm',
            $code >= 300 && $code <= 321 => 'Drizzle',
            $code >= 600 && $code <= 622 => 'Snow',
            $code >= 701 && $code <= 781 => 'Foggy',
            default => ucfirst($main),
        };
    }

    /**
     * Map an OpenWeatherMap weather condition code to an icon name used by the widget.
     */
    private static function getWeatherIcon(int $code, string $main): string
    {
        return match (true) {
            $code === 800 => 'sun',
            $code === 801 => 'cloud-sun',
            $code === 802, $code === 803, $code === 804 => 'cloud',
            $code >= 500 && $code <= 531 => 'cloud-rain',
            $code >= 200 && $code <= 232 => 'cloud-lightning',
            $code >= 300 && $code <= 321 => 'cloud-drizzle',
            $code >= 600 && $code <= 622 => 'cloud-snow',
            $code >= 701 && $code <= 781 => 'cloud',
            default => 'cloud',
        };
    }

    /**
     * Realistic, stable fallback weather for Bulan, Sorsogon.
     *
     * Values are derived deterministically from the current season and time of
     * day (NOT random), so they remain consistent across page refreshes.
     *
     * @return array
     */
    private static function getFallbackWeather(): array
    {
        $season = SeasonHelper::getCurrentSeason();
        $hour = now()->hour;
        $isNight = $hour < 6 || $hour > 18;

        // Stable base temperatures by season.
        $baseTemp = $season === 'wet' ? 28.5 : 30.5;

        // Add a small, deterministic diurnal variation (no rand()).
        if ($isNight) {
            $temp = $baseTemp - 4.0;
        } elseif ($hour >= 11 && $hour <= 15) {
            $temp = $baseTemp + 2.5;
        } else {
            $temp = $baseTemp;
        }

        $humidity = $season === 'wet' ? 84 : 70;
        $wind = $season === 'wet' ? 18 : 12;
        $uv = ($hour >= 6 && $hour <= 17) ? ($season === 'wet' ? 6 : 9) : 0;
        $rainProb = $season === 'wet' ? 65 : 20;

        $condition = $rainProb > 60 ? 'Rain Showers' : 'Partly Cloudy';
        $icon = $rainProb > 60 ? 'cloud-rain' : 'cloud-sun';

        return [
            'temperature'      => round($temp, 1),
            'feels_like'       => round($temp + ($humidity > 80 ? 1.5 : 0), 1),
            'humidity'         => $humidity,
            'wind_speed'       => round($wind, 1),
            'uv_index'         => $uv,
            'rain_probability' => $rainProb,
            'rain'             => $season === 'wet' ? 2.5 : 0,
            'precipitation'    => $season === 'wet' ? 2.5 : 0,
            'weather_code'     => $rainProb > 60 ? 501 : 801,
            'condition'        => $condition,
            'icon'             => $icon,
            'season'           => $season,
            'location'         => self::LOCATION_NAME,
            'country'          => self::COUNTRY,
            'latitude'         => self::LATITUDE,
            'longitude'        => self::LONGITUDE,
            'updated_at'       => now()->format('Y-m-d H:i:s'),
            'is_real'          => false,
            'is_real_data'     => false,
        ];
    }

    /**
     * Realistic, stable fallback hourly forecast for Bulan, Sorsogon.
     *
     * @return array
     */
    private static function getFallbackHourly(): array
    {
        $season = SeasonHelper::getCurrentSeason();
        $now = now()->timezone(self::TIMEZONE);
        $hours = [];

        $baseTemp = $season === 'wet' ? 28.5 : 30.5;

        for ($i = 0; $i < 24; $i++) {
            $hourTime = $now->copy()->addHours($i);
            $hourOfDay = $hourTime->hour;

            // Deterministic diurnal temperature curve.
            if ($hourOfDay >= 0 && $hourOfDay <= 5) {
                $temp = $baseTemp - 4.0;
            } elseif ($hourOfDay >= 11 && $hourOfDay <= 15) {
                $temp = $baseTemp + 2.5;
            } else {
                $temp = $baseTemp;
            }

            // Rain probability higher in wet-season afternoons.
            $rainProb = $season === 'wet' ? 65 : 20;
            if ($season === 'wet' && $hourOfDay >= 13 && $hourOfDay <= 18) {
                $rainProb = 80;
            }

            $hours[] = [
                'time'              => $hourTime->format('g A'),
                'formatted_time'    => $hourTime->format('H:i'),
                'hour'              => $hourOfDay,
                'temperature'       => round($temp, 1),
                'rain_probability'  => $rainProb,
                'temp'              => round($temp, 1),
                'rain_prob'         => $rainProb,
                'pop'               => $rainProb,
                'humidity'          => $season === 'wet' ? 84 : 70,
                'wind_speed'        => $season === 'wet' ? 18 : 12,
                'uv_index'          => ($hourOfDay >= 6 && $hourOfDay <= 17) ? ($season === 'wet' ? 6 : 9) : 0,
                'weather_code'      => $rainProb > 60 ? 501 : 801,
                'condition'         => $rainProb > 60 ? 'Rain Showers' : 'Partly Cloudy',
                'icon'              => $rainProb > 60 ? 'cloud-rain' : 'cloud-sun',
            ];
        }

        return $hours;
    }
}
