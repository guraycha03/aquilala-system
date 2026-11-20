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
     * Cache entry identifier for the current-weather snapshot.
     */
    const CACHE_KEY = 'weather_bulan_sorsogon';

    /**
     * Freshness window in seconds (15 minutes). While cached data is younger
     * than this it is served directly with no API call.
     */
    const CACHE_TTL = 900;

    /**
     * How long a stale snapshot is retained as a fallback (seconds, 24 hours).
     * Deliberately longer than the freshness window so that when the live API
     * call fails we can still fall back to the last-known-good data instead of
     * breaking the UI.
     */
    const CACHE_STALE_TTL = 86400;

    /**
     * Cache duration in seconds (30 minutes) used by the hourly forecast.
     */
    const CACHE_DURATION = 1800;

    /**
     * Cooldown marker written after a failed API call (seconds).
     *
     * Without it a dead/slow network turns into a retry on every single page
     * render (and on every background widget refresh), which is what made the
     * whole app feel frozen for minutes at a time. One attempt per minute is
     * plenty to recover as soon as the API is reachable again.
     */
    const FAILURE_COOLDOWN = 60;

    /**
     * Cache entry holding the failed-call cooldown marker.
     */
    const COOLDOWN_KEY = 'weather_bulan_sorsogon_cooldown';

    /**
     * Read the cached weather snapshot without touching the network.
     *
     * Used by the sidebar layout so rendering a page can never block on
     * OpenWeather. Returns null when there is no usable snapshot (the caller
     * then renders a placeholder and lets the browser fetch /weather/current).
     *
     * Stale-but-real readings are returned too — a temperature from a few
     * minutes ago is still honest, and `updated_at` carries its age.
     */
    public static function cachedWeather(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached) && !empty($cached['is_available'])) {
            return $cached;
        }

        return null;
    }

    /**
     * Get the current weather for Bulan, Sorsogon with a read-through cache.
     *
     * Cache + fallback strategy (keeps the OpenWeather free tier from being
     * hammered on every page load):
     *
     *   1. If a fresh snapshot exists (< 15 minutes old) it is returned
     *      immediately — no API call is made.
     *   2. If the last API call failed within FAILURE_COOLDOWN seconds, the
     *      expired snapshot (or an unavailable payload) is returned without
     *      re-trying, so a dead network costs one attempt per minute instead
     *      of one per request.
     *   3. Otherwise the OpenWeather /data/2.5/weather endpoint is called,
     *      the result replaces the cache with a fresh timestamp, and is
     *      returned.
     *   4. If the live call fails, the expired snapshot is returned as a
     *      fallback so the UI keeps working. Only when there is nothing
     *      cached at all is an "unavailable" payload returned.
     *
     * Only real API values are ever served as fresh data — never fabricated
     * or simulated numbers.
     *
     * @return array
     */
    public static function getCurrentWeather(): array
    {
        // 1) Return a fresh cached snapshot right away — no API round-trip.
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached) && self::isFresh($cached)) {
            return $cached;
        }

        // 2) The last attempt failed recently — reuse the cached snapshot (or
        //    report unavailable) instead of re-trying on every request.
        if (Cache::has(self::COOLDOWN_KEY)) {
            if (is_array($cached)) {
                $cached['stale'] = true;

                return $cached;
            }

            return self::unavailable();
        }

        // 3) Cache is missing or stale — refresh from the API and repopulate
        //    the cache (longer retention than the freshness window so a later
        //    failed refresh can still fall back to this snapshot).
        $fresh = self::fetchCurrentWeather();

        if (!empty($fresh['is_available'])) {
            Cache::put(self::CACHE_KEY, $fresh, self::CACHE_STALE_TTL);

            return $fresh;
        }

        // Live API call failed — arm the cooldown, then fall back to the
        // expired snapshot so the UI does not break.
        Cache::put(self::COOLDOWN_KEY, true, self::FAILURE_COOLDOWN);

        if (is_array($cached)) {
            $cached['stale'] = true;

            return $cached;
        }

        // Nothing cached and the API failed — honest "unavailable" payload.
        return self::unavailable();
    }

    /**
     * Call the OpenWeather /data/2.5/weather endpoint and build a real payload.
     *
     * Returns an unavailable payload (is_available = false) on any failure —
     * missing key, network error, non-2xx response — never invented data.
     */
    private static function fetchCurrentWeather(): array
    {
        $apiKey = config('services.openweather.key');

        // No API key configured? Report unavailable rather than invent data.
        if (empty($apiKey)) {
            return self::unavailable();
        }

        try {
            $response = self::request(config('services.openweather.url'), [
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
            // Network failure, invalid key, etc. — handled by the caller's
            // stale-fallback logic.
        }

        return self::unavailable();
    }

    /**
     * Whether a cached snapshot is still within the freshness window.
     *
     * A previously-cached "unavailable" payload is never treated as fresh so
     * the API is always retried instead of serving a dead cache.
     */
    private static function isFresh(array $cached): bool
    {
        if (empty($cached['is_available'])) {
            return false;
        }

        $updatedAt = strtotime((string) ($cached['updated_at'] ?? ''));

        return $updatedAt !== false && (time() - $updatedAt) < self::CACHE_TTL;
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
                $response = self::request(config('services.openweather.forecast_url'), [
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
     * Tight connect/request caps keep the background /weather/current call
     * short-lived when the network stalls. Verifies TLS against a local CA
     * bundle at storage/certs/cacert.pem when present (e.g. for Windows/CLI
     * environments without a system bundle), falling back to Guzzle's default
     * verification otherwise.
     */
    private static function http(bool $verify = true): \Illuminate\Http\Client\PendingRequest
    {
        if (! $verify) {
            return Http::timeout(4)->connectTimeout(2)->withoutVerifying();
        }

        $cacert = storage_path('certs/cacert.pem');
        if (is_file($cacert)) {
            return Http::timeout(4)->connectTimeout(2)->withOptions(['verify' => $cacert]);
        }
        return Http::timeout(4)->connectTimeout(2);
    }

    /**
     * GET the weather API, retrying once without TLS verification when this
     * machine cannot complete the handshake against any CA bundle.
     *
     * Some Windows/CLI PHP installs time out on cURL's TLS verification even
     * though the endpoint is reachable — the AI client already has to use the
     * same fallback. The retry still reads the real API; it never substitutes
     * invented weather. The failure is remembered for the rest of the request
     * so a second weather question is not delayed by a doomed first attempt.
     */
    private static function request(string $url, array $query)
    {
        static $skipVerification = false;

        if (! $skipVerification) {
            try {
                return self::http()->get($url, $query);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $skipVerification = true;
            }
        }

        return self::http(false)->get($url, $query);
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