<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherWidgetTest extends TestCase
{
    use RefreshDatabase;

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

    private function weatherRequests(): int
    {
        return collect(Http::recorded())
            ->filter(fn ($pair) => str_contains((string) $pair[0]->url(), 'openweathermap.org'))
            ->count();
    }

    private function makeUser(): User
    {
        return User::create([
            'name'       => 'Juan Dela Cruz',
            'first_name' => 'Juan',
            'last_name'  => 'Dela Cruz',
            'email'      => 'farmer_' . uniqid() . '@example.com',
            'role'       => 'farmer',
            'password'   => 'password123',
        ]);
    }

    public function test_widget_endpoint_serves_the_cached_reading_without_an_api_call(): void
    {
        $this->fakeApiSuccess();
        cache()->put(\App\Services\WeatherService::CACHE_KEY, $this->snapshot(), \App\Services\WeatherService::CACHE_STALE_TTL);

        $response = $this->actingAs($this->makeUser())->getJson(route('weather.current'));

        $response->assertOk()->assertJson(['temperature' => 28.5, 'is_available' => true]);
        $this->assertSame(0, $this->weatherRequests());
    }

    public function test_widget_endpoint_refreshes_from_the_api_when_the_cache_is_cold(): void
    {
        $this->fakeApiSuccess();
        cache()->forget(\App\Services\WeatherService::CACHE_KEY);

        $response = $this->actingAs($this->makeUser())->getJson(route('weather.current'));

        $response->assertOk()->assertJson(['temperature' => 31.0, 'is_available' => true]);
        $this->assertSame(1, $this->weatherRequests());
    }

    public function test_widget_endpoint_requires_authentication(): void
    {
        $this->fakeApiSuccess();

        $this->get(route('weather.current'))->assertRedirect(route('login'));
        $this->assertSame(0, $this->weatherRequests());
    }

    public function test_sidebar_page_renders_the_cached_reading(): void
    {
        $this->fakeApiSuccess();
        cache()->put(\App\Services\WeatherService::CACHE_KEY, $this->snapshot(), \App\Services\WeatherService::CACHE_STALE_TTL);

        $html = $this->actingAs($this->makeUser())->get('/farmer/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('28.5°C', $html);
        $this->assertStringContainsString('data-weather-loaded="1"', $html);
        $this->assertSame(0, $this->weatherRequests(), 'A warm cache must not trigger any API call during render.');
    }

    public function test_sidebar_page_with_a_cold_cache_renders_a_placeholder_instead_of_blocking_on_the_api(): void
    {
        $this->fakeApiSuccess();
        cache()->forget(\App\Services\WeatherService::CACHE_KEY);

        $html = $this->actingAs($this->makeUser())->get('/farmer/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Loading weather', $html);
        $this->assertStringContainsString('data-weather-loaded="0"', $html);
        $this->assertStringContainsString('weather/current', $html);
        $this->assertSame(0, $this->weatherRequests(), 'Rendering must never wait on the weather API.');
    }
}
