<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class WeatherService
{
    const LATITUDE = 12.6700;
    const LONGITUDE = 123.8800;
    const LOCATION_NAME = 'Bulan, Sorsogon';
    const TIMEZONE = 'Asia/Manila';
    const COUNTRY = 'Philippines';

    public static function getCurrentWeather(): array
    {
        $cacheKey = 'weather_current';
        $cached = Cache::get($cacheKey);
        if ($cached) return $cached;

        try {
            $response = Http::timeout(10)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => self::LATITUDE,
                'longitude' => self::LONGITUDE,
                'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,rain,weather_code,wind_speed_10m,uv_index',
                'timezone' => self::TIMEZONE,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $current = $data['current'] ?? [];
                
                $weather = [
                    'temperature' => round($current['temperature_2m'] ?? 28, 1),
                    'feels_like' => round($current['apparent_temperature'] ?? 28, 1),
                    'humidity' => $current['relative_humidity_2m'] ?? 75,
                    'wind_speed' => round($current['wind_speed_10m'] ?? 10, 1),
                    'uv_index' => round($current['uv_index'] ?? 0, 1),
                    'rain' => $current['rain'] ?? 0,
                    'precipitation' => $current['precipitation'] ?? 0,
                    'weather_code' => $current['weather_code'] ?? 0,
                    'condition' => self::getWeatherCondition($current['weather_code'] ?? 0),
                    'icon' => self::getWeatherIcon($current['weather_code'] ?? 0),
                    'season' => \App\Helpers\SeasonHelper::getCurrentSeason(),
                    'location' => self::LOCATION_NAME,
                    'country' => self::COUNTRY,
                    'latitude' => self::LATITUDE,
                    'longitude' => self::LONGITUDE,
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                    'is_real' => true,
                ];

                Cache::put($cacheKey, $weather, now()->addMinutes(30));
                return $weather;
            }
        } catch (\Exception $e) {
            return self::getFallbackWeather();
        }

        return self::getFallbackWeather();
    }

    public static function getHourlyForecast(): array
    {
        $cacheKey = 'weather_hourly_v2';
        $cached = Cache::get($cacheKey);
        if ($cached) return $cached;

        try {
            $response = Http::timeout(10)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => self::LATITUDE,
                'longitude' => self::LONGITUDE,
                'hourly' => 'temperature_2m,precipitation_probability,relative_humidity_2m,wind_speed_10m,uv_index,weather_code',
                'timezone' => self::TIMEZONE,
                'forecast_days' => 2, // Fetch 2 days so we can slice exact next 24 hours
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $hourly = $data['hourly'] ?? [];
                $hours = [];

                if (isset($hourly['time']) && is_array($hourly['time'])) {
                    $now = now()->timezone(self::TIMEZONE);

                    foreach ($hourly['time'] as $i => $timeStr) {
                        $carbonTime = \Carbon\Carbon::parse($timeStr, self::TIMEZONE);
                        
                        // Skip past hours
                        if ($carbonTime->lt($now->copy()->subHour())) {
                            continue;
                        }

                        $tempVal = round($hourly['temperature_2m'][$i] ?? 28, 1);
                        $rainVal = $hourly['precipitation_probability'][$i] ?? 0;

                        $hours[] = [
                            'time' => $carbonTime->format('g A'), // e.g., '2 PM'
                            'formatted_time' => $carbonTime->format('H:i'), // e.g., '14:00'
                            'hour' => $carbonTime->hour,
                            // Direct key
                            'temperature' => $tempVal,
                            'rain_probability' => $rainVal,
                            // Alias keys to ensure backward compatibility with all Blade templates
                            'temp' => $tempVal,
                            'rain_prob' => $rainVal,
                            'pop' => $rainVal,
                            'humidity' => $hourly['relative_humidity_2m'][$i] ?? 75,
                            'wind_speed' => round($hourly['wind_speed_10m'][$i] ?? 10, 1),
                            'uv_index' => round($hourly['uv_index'][$i] ?? 0, 1),
                            'weather_code' => $hourly['weather_code'][$i] ?? 0,
                            'condition' => self::getWeatherCondition($hourly['weather_code'][$i] ?? 0),
                            'icon' => self::getWeatherIcon($hourly['weather_code'][$i] ?? 0),
                        ];

                        // Stop once we have 24 hours
                        if (count($hours) >= 24) {
                            break;
                        }
                    }
                }

                if (!empty($hours)) {
                    Cache::put($cacheKey, $hours, now()->addMinutes(30));
                    return $hours;
                }
            }
        } catch (\Exception $e) {
            return self::getFallbackHourly();
        }

        return self::getFallbackHourly();
    }

    public static function getWeatherAlerts(): array
    {
        try {
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

            $maxRainProb = collect($hourly)->max('rain_probability');
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
        } catch (\Exception $e) {
            return \App\Services\WeatherSimulationService::getWeatherAlerts();
        }
    }

    public static function getWeatherContextForEngine(): string
    {
        try {
            $weather = self::getCurrentWeather();
            $hourly = self::getHourlyForecast();
            $season = \App\Helpers\SeasonHelper::getCurrentSeason();
            
            $maxRain = collect($hourly)->max('rain_probability');
            $maxTemp = collect($hourly)->max('temperature');
            $maxWind = collect($hourly)->max('wind_speed');

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
        } catch (\Exception $e) {
            return 'normal';
        }
    }

    private static function getWeatherCondition(int $code): string
    {
        return match(true) {
            $code === 0 => 'Clear Sky',
            $code <= 3 => 'Partly Cloudy',
            $code <= 19 => 'Foggy',
            $code <= 29 => 'Thunderstorm',
            $code <= 49 => 'Drizzle',
            $code <= 59 => 'Rain',
            $code <= 69 => 'Snow',
            $code <= 79 => 'Rain Showers',
            $code <= 84 => 'Rain',
            $code <= 99 => 'Thunderstorm',
            default => 'Cloudy',
        };
    }

    private static function getWeatherIcon(int $code): string
    {
        return match(true) {
            $code === 0 => 'sun',
            $code <= 3 => 'cloud-sun',
            $code <= 19 => 'cloud',
            $code <= 29 => 'cloud-lightning',
            $code <= 49 => 'cloud-drizzle',
            $code <= 59 => 'cloud-rain',
            $code <= 79 => 'cloud-rain',
            $code <= 84 => 'cloud-rain',
            $code <= 99 => 'cloud-lightning',
            default => 'cloud',
        };
    }

    private static function getFallbackWeather(): array
    {
        return \App\Services\WeatherSimulationService::getCurrentWeather();
    }

    private static function getFallbackHourly(): array
    {
        return \App\Services\WeatherSimulationService::getHourlyForecast();
    }
}