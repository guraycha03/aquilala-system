<?php

namespace Tests\Feature;

use App\Services\WeatherService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherServiceCacheTest extends TestCase
{
    private function snapshot(int $minutesAgo = 0, bool $available = true): array
    {
        return [
            'temperature'   => 28.5,
            'condition'     => 'Partly Cloudy',
            'icon'          => 'cloud-sun',
            'season'        => 'dry',
            'location'      => 'Bulan, Sorsogon',
            'updated_at'    => now()->subMinutes($minutesAgo)->format('Y-m-d H:i:s'),
            'is_real'       => true,
            'is_real_data'  => true,
            'is_available'  => $available,
        ];
    }

    private function fakeApiSuccess(): void
    {
        Http::fake(fn () => Http::response([
            'main'    => ['temp' => 31.0, 'humidity' => 70],
            'weather' => [['id' => 801, 'main' => 'Clouds']],
            'wind'    => ['speed' => 2.5],
        ], 200));
    }

    private function fakeApiFailure(): void
    {
        Http::fake(fn () => Http::response([], 500));
    }

    public function test_fresh_cache_is_served_without_an_api_call(): void
    {
        $this->fakeApiSuccess();
        cache()->put(WeatherService::CACHE_KEY, $this->snapshot(), WeatherService::CACHE_STALE_TTL);

        $result = WeatherService::getCurrentWeather();

        Http::assertNothingSent();
        $this->assertSame(28.5, $result['temperature']);
        $this->assertTrue($result['is_available']);
    }

    public function test_expired_cache_triggers_refresh_and_updates_the_cache(): void
    {
        $this->fakeApiSuccess();
        cache()->put(WeatherService::CACHE_KEY, $this->snapshot(20), WeatherService::CACHE_STALE_TTL);

        $result = WeatherService::getCurrentWeather();

        $this->assertSame(31.0, $result['temperature']);
        $this->assertTrue($result['is_available']);
        $this->assertArrayNotHasKey('stale', $result);

        $cached = cache()->get(WeatherService::CACHE_KEY);
        $this->assertSame(31.0, $cached['temperature']);
        $this->assertSame($result['updated_at'], $cached['updated_at']);
    }

    public function test_expired_cache_with_failed_api_falls_back_to_stale_data(): void
    {
        $this->fakeApiFailure();
        cache()->put(WeatherService::CACHE_KEY, $this->snapshot(20), WeatherService::CACHE_STALE_TTL);

        $result = WeatherService::getCurrentWeather();

        $this->assertTrue($result['is_available'], 'Stale snapshot should be returned.');
        $this->assertTrue($result['stale'] ?? false, 'Fallback data must be flagged stale.');
        $this->assertSame(28.5, $result['temperature']);
    }

    public function test_empty_cache_fetches_fresh_data_and_populates_the_cache(): void
    {
        $this->fakeApiSuccess();
        cache()->forget(WeatherService::CACHE_KEY);

        $result = WeatherService::getCurrentWeather();

        $this->assertSame(31.0, $result['temperature']);
        $this->assertTrue($result['is_available']);
        $this->assertSame(31.0, cache()->get(WeatherService::CACHE_KEY)['temperature']);
    }

    public function test_empty_cache_with_failed_api_returns_unavailable(): void
    {
        $this->fakeApiFailure();
        cache()->forget(WeatherService::CACHE_KEY);

        $result = WeatherService::getCurrentWeather();

        $this->assertFalse($result['is_available']);
    }

    public function test_cached_unavailable_payload_is_not_served_as_fresh(): void
    {
        $this->fakeApiSuccess();
        cache()->put(WeatherService::CACHE_KEY, $this->snapshot(0, false), WeatherService::CACHE_STALE_TTL);

        $result = WeatherService::getCurrentWeather();

        $this->assertTrue(Http::recorded() !== [], 'API must be retried instead of serving a dead cache.');
        $this->assertSame(31.0, $result['temperature']);
        $this->assertTrue($result['is_available']);
    }
}