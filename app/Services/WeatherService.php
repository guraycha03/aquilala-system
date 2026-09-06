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
     * Get the current weather for Bulan, Sorsogon from the free
     * OpenWeatherMap /data/2.5/weather endpoint.
     *
     * Only real API values are returned. If the API is unreachable, missing,
     * or rejects the request, an "unavailable" payload (is_available = false,
     * null metrics) is returned — never fabricated or simulated numbers.
     *
     * @return array
     */
    public static function getCurrentWeather(): array
    {
        return Cache::remember('weather_bulan_sorsogon', self::CACHE_DURATION, function () {
            $apiKey = config('services.openweather.key');

            // No API key configured? Report unavailable rather than invent data.
            if (empty($apiKey)) {
                return self::unavailable();
            }

            try {
                $response = self::http()
                    ->get(config('services.openweather.url'), [
                        'lat' => self::LATITUDE,
                        'lon' => self::LONGITUDE,
                        'units' => 'metric',
                        'appid' => $apiKey,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $main = $data['main'] ?? [];
                    $weatherCodeData = $data['weather'][0] ?? [];

                    $weatherCode = (int) ($weatherCodeData['id'] ?? 0);
                    $weatherMain = strtolower($weatherCodeData['main'] ?? 'Clouds');
                    $rain = (float) ($data['rain']['1h'] ?? $data['rain']['3h'] ?? 0);
                    $snow = (float) ($data['snow']['1h'] ?? $data['snow']['3h'] ?? 0);

                    return [
                        'temperature'   => isset($main['temp']) ? round((float) $main['temp'], 1) : null,
                        'feels_like'    => isset($main['feels_like']) ? round((float) $main['feels_like'], 1) : null,
                        'humidity'      => $main['humidity'] ?? null,
                        'wind_speed'    => isset($data['wind']['speed']) ? round((float) $data['wind']['speed'] * 3.6, 1) : null, // m/s -> km/h
                        'uv_index'      => null, // UV index is not part of the free 2.5 endpoints
                        'rain'          => $rain,
                        'precipitation' => $rain + $snow,
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
                        'is_available'  => true,
                    ];
                }
            } catch (\Exception $e) {
                // Network failure, invalid key, etc. — report unavailable.
            }

            return self::unavailable();
        });
    }

    /**
     * Get the forecast for the next 24 hours from the free
     * OpenWeatherMap /data/2.5/forecast endpoint (3-hour steps).
     *
     * Returns only real forecast entries. An empty array means the forecast
     * could not be fetched — never invented data.
     *
     * @return array
     */
    public static function getHourlyForecast(): array
    {
        return Cache::remember('weather_bulan_sorsogon_hourly', self::CACHE_DURATION, function () {
            $apiKey = config('services.openweather.key');

            if (empty($apiKey)) {
                return [];
            }

            try {
                $response = self::http()
                    ->get(config('services.openweather.forecast_url'), [
                        'lat' => self::LATITUDE,
                        'lon' => self::LONGITUDE,
                        'units' => 'metric',
                        'appid' => $apiKey,
                    ]);

                if ($response->successful()) {
                    $list = $response->json()['list'] ?? [];
                    $hours = [];
                    $now = now()->timezone(self::TIMEZONE);

                    foreach ($list as $item) {
                        if (!is_array($item) || ($item['dt'] ?? null) === null) {
                            continue;
                        }

                        $carbonTime = Carbon::createFromTimestamp($item['dt'])->timezone(self::TIMEZONE);

                        // Skip past entries.
                        if ($carbonTime->lt($now->copy()->subHour())) {
                            continue;
                        }

                        // Limit to the next 24 hours of forecast.
                        if ($carbonTime->gt($now->copy()->addHours(24))) {
                            break;
                        }

                        $weatherCodeData = $item['weather'][0] ?? [];
                        $weatherCode = (int) ($weatherCodeData['id'] ?? 0);
                        $weatherMain = strtolower($weatherCodeData['main'] ?? 'Clouds');
                        $mainBlock = $item['main'] ?? [];

                        $hours[] = [
                            'time'              => $carbonTime->format('g A'),
                            'formatted_time'    => $carbonTime->format('H:i'),
                            'hour'              => $carbonTime->hour,
                            'temperature'       => isset($mainBlock['temp']) ? round((float) $mainBlock['temp'], 1) : null,
                            'rain_probability'  => round((float) ($item['pop'] ?? 0) * 100, 0), // 0-1 -> %
                            'temp'              => isset($mainBlock['temp']) ? round((float) $mainBlock['temp'], 1) : null,
                            'rain_prob'         => round((float) ($item['pop'] ?? 0) * 100, 0),
                            'pop'               => round((float) ($item['pop'] ?? 0) * 100, 0),
                            'humidity'          => $mainBlock['humidity'] ?? null,
                            'wind_speed'        => isset($item['wind']['speed']) ? round((float) $item['wind']['speed'] * 3.6, 1) : null,
                            'uv_index'          => null,
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
                // Report empty forecast rather than invented data.
            }

            return [];
        });
    }

    /**
     * Generate weather alerts based strictly on real current conditions and forecast.
     *
     * Returns an empty array when no real data is available (an alert must
     * never be invented).
     *
     * @return array
     */
    public static function getWeatherAlerts(): array
    {
        $weather = self::getCurrentWeather();
        $hourly = self::getHourlyForecast();

        if (empty($weather['is_available'])) {
            return [];
        }

        $alerts = [];

        if ($weather['temperature'] !== null && $weather['temperature'] >= 35) {
            $alerts[] = [
                'type' => 'warning',
                'severity' => 'moderate',
                'title' => 'Extreme Heat Advisory',
                'message' => "Current temperature is {$weather['temperature']}°C. Prolonged exposure may cause heat stress to crops and livestock.",
                'action_tip' => 'Irrigate during early morning or evening. Provide shade for seedlings. Monitor for heat stress symptoms.',
                'icon' => 'alert-triangle',
                'color' => '#d97706',
                'bg_color' => '#fef3c7',
            ];
        }

        if ($weather['wind_speed'] !== null && $weather['wind_speed'] >= 40) {
            $alerts[] = [
                'type' => 'warning',
                'severity' => 'moderate',
                'title' => 'Strong Wind Advisory',
                'message' => "Wind speeds reaching {$weather['wind_speed']} km/h. Potential damage to tall crops and structures.",
                'action_tip' => 'Secure lightweight structures. Delay spraying operations. Support tall crops if needed.',
                'icon' => 'wind',
                'color' => '#d97706',
                'bg_color' => '#fef3c7',
            ];
        }

        $maxRainProb = collect($hourly)->max('rain_probability');
        if (!empty($hourly) && $maxRainProb !== null && $maxRainProb >= 70) {
            $alerts[] = [
                'type' => 'warning',
                'severity' => 'moderate',
                'title' => 'Heavy Rain Expected',
                'message' => "Rain probability reaching {$maxRainProb}% in the next hours. Prepare for possible field flooding.",
                'action_tip' => 'Check drainage systems. Delay fertilizer/pesticide application. Monitor low-lying areas.',
                'icon' => 'cloud-rain',
                'color' => '#0284c7',
                'bg_color' => '#e0f2fe',
            ];
        }

        if (empty($alerts)) {
            $alerts[] = [
                'type' => 'success',
                'severity' => 'normal',
                'title' => 'Normal Conditions',
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
     * Returns 'normal' when real data is unavailable — we never guess at
     * severe conditions.
     *
     * @return string
     */
    public static function getWeatherContextForEngine(): string
    {
        $weather = self::getCurrentWeather();
        $hourly = self::getHourlyForecast();

        if (empty($weather['is_available']) || empty($hourly)) {
            return 'normal';
        }

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
     *
     * Codes are tuned for the tropical Philippine context (Bulan, Sorsogon):
     * snow/sleet codes (600–622) and freezing rain (511) are bundled into
     * "Rain" since frozen precipitation does not occur here.
     */
    private static function getWeatherCondition(int $code, string $main): string
    {
        return match (true) {
            $code === 800 => 'Clear Sky',
            $code === 801 => 'Partly Cloudy',
            $code === 802, $code === 803, $code === 804 => 'Cloudy',
            $code >= 500 && $code <= 622 => 'Rain',
            $code >= 200 && $code <= 232 => 'Thunderstorm',
            $code >= 300 && $code <= 321 => 'Drizzle',
            $code >= 701 && $code <= 781 => 'Foggy',
            default => ucfirst($main),
        };
    }

    /**
     * Map an OpenWeatherMap weather condition code to an icon name used by the widget.
     *
     * Tropical-context mapping (Bulan, Sorsogon): snow/sleet codes fall back to
     * the rain icon — a paper snowflake icon has no real use here.
     */
    private static function getWeatherIcon(int $code, string $main): string
    {
        return match (true) {
            $code === 800 => 'sun',
            $code === 801 => 'cloud-sun',
            $code === 802, $code === 803, $code === 804 => 'cloud',
            $code >= 500 && $code <= 622 => 'cloud-rain',
            $code >= 200 && $code <= 232 => 'cloud-lightning',
            $code >= 300 && $code <= 321 => 'cloud-drizzle',
            $code >= 701 && $code <= 781 => 'cloud',
            default => 'cloud',
        };
    }

    /**
     * Shared HTTP client for OpenWeatherMap requests.
     *
     * Verifies TLS against a local CA bundle at storage/certs/cacert.pem when
     * present (e.g. for Windows/CLI environments without a system bundle).
     * Falls back to Guzzle's default verification otherwise.
     */
    private static function http(): \Illuminate\Http\Client\PendingRequest
    {
        $cacert = storage_path('certs/cacert.pem');
        if (is_file($cacert)) {
            return Http::timeout(10)->withOptions(['verify' => $cacert]);
        }
        return Http::timeout(10);
    }

    /**
     * Honest "weather unavailable" payload — no fabricated values.
     *
     * @return array
     */
    private static function unavailable(): array
    {
        return [
            'temperature'   => null,
            'feels_like'    => null,
            'humidity'      => null,
            'wind_speed'    => null,
            'uv_index'      => null,
            'rain'          => null,
            'precipitation' => null,
            'weather_code'  => null,
            'condition'     => 'Weather unavailable',
            'icon'          => 'cloud',
            'season'        => SeasonHelper::getCurrentSeason(),
            'location'      => self::LOCATION_NAME,
            'country'       => self::COUNTRY,
            'latitude'      => self::LATITUDE,
            'longitude'     => self::LONGITUDE,
            'updated_at'    => now()->format('Y-m-d H:i:s'),
            'is_real'       => false,
            'is_real_data'  => false,
            'is_available'  => false,
        ];
    }
}