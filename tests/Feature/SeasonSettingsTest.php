<?php

namespace Tests\Feature;

use App\Helpers\SeasonHelper;
use App\Helpers\SettingsHelper;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin → Settings → Season Settings.
 *
 * The page used to be a stub: four month selects and a Save button that
 * only showed a toast. This covers the real behaviour now — the admin's
 * windows are stored, and SeasonHelper (the one place the whole system
 * asks "which season is it?") reads them back, so a save reaches the
 * sidebar card, planting forms, quotations and the AI at once.
 *
 * Defaults are PAGASA: wet June–November, dry December–May.
 */
class SeasonSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $farmer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->makeUser('Seasons Admin', 'admin');
        $this->farmer = $this->makeUser('Seasons Farmer', 'farmer');
        $this->resetSettingsCache();
    }

    protected function tearDown(): void
    {
        // SettingsHelper caches the settings table in a static; without this
        // a value written here would survive the rollback and leak into the
        // next test running in the same process.
        $this->resetSettingsCache();

        parent::tearDown();
    }

    // ── Defaults ─────────────────────────────────────────────────

    public function test_a_fresh_install_uses_the_pagasa_defaults(): void
    {
        $this->assertSame([6, 11], SeasonHelper::wetWindow());
        $this->assertSame('June – November', SeasonHelper::rangeLabel('wet'));
        $this->assertSame('December – May', SeasonHelper::rangeLabel('dry'));

        $this->assertSame('wet', SeasonHelper::getCurrentSeason(Carbon::parse('2026-06-15')));
        $this->assertSame('wet', SeasonHelper::getCurrentSeason(Carbon::parse('2026-11-30')));
        $this->assertSame('dry', SeasonHelper::getCurrentSeason(Carbon::parse('2026-12-01')));
        $this->assertSame('dry', SeasonHelper::getCurrentSeason(Carbon::parse('2026-05-31')));
    }

    // ── Page ─────────────────────────────────────────────────────

    public function test_the_season_page_shows_the_windows_in_use(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.seasons'))
            ->assertOk()
            ->assertSee('Wet Season Window')
            ->assertSee('Dry Season Window')
            ->assertSee('PAGASA')
            ->assertSee('Derived automatically');
    }

    public function test_a_farmer_cannot_change_the_seasons(): void
    {
        $this->actingAs($this->farmer)
            ->get(route('admin.settings.seasons'))
            ->assertForbidden();

        $this->actingAs($this->farmer)
            ->postJson(route('admin.settings.seasons.store'), $this->payload())
            ->assertForbidden();
    }

    // ── Saving ───────────────────────────────────────────────────

    public function test_an_admin_can_change_the_windows_and_the_system_follows(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.settings.seasons.store'), [
                'wet_start' => 1,
                'wet_end' => 5,
                'active_season' => 'auto',
                'season_reco' => true,
            ]);

        $response->assertOk()->assertJsonPath('ok', true);
        $response->assertJsonPath('wet', 'January – May');
        $response->assertJsonPath('dry', 'June – December');

        // The whole system reads the same helper, so these are the values
        // every consumer (sidebar, planting, quotations, AI) now sees.
        $this->assertSame([1, 5], SeasonHelper::wetWindow());
        $this->assertSame('wet', SeasonHelper::getCurrentSeason(Carbon::parse('2026-02-10')));
        $this->assertSame('dry', SeasonHelper::getCurrentSeason(Carbon::parse('2026-06-10')));
        $this->assertSame('June – December', SeasonHelper::getClimateInfo()['months']);
        $this->assertSame('Dry season (Jun–Dec)', app(\App\Services\CropLibraryService::class)->seasonLabel(['dry']));
    }

    public function test_forcing_a_season_overrides_the_calendar(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.seasons.store'), [
                'wet_start' => 6,
                'wet_end' => 11,
                'active_season' => 'wet',
            ])
            ->assertOk();

        // December is dry by the calendar, but the admin forced wet.
        $this->assertSame('wet', SeasonHelper::getCurrentSeason(Carbon::parse('2026-12-20')));

        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.seasons.store'), [
                'wet_start' => 6,
                'wet_end' => 11,
                'active_season' => 'auto',
            ])
            ->assertOk();

        $this->assertSame('dry', SeasonHelper::getCurrentSeason(Carbon::parse('2026-12-20')));
    }

    public function test_invalid_windows_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.seasons.store'), [
                'wet_start' => 13,
                'wet_end' => 11,
                'active_season' => 'auto',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['wet_start']);

        // Nothing stored, so the defaults still hold.
        $this->assertSame([6, 11], SeasonHelper::wetWindow());
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function makeUser(string $name, string $role): User
    {
        return User::create([
            'name'       => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name'  => explode(' ', $name)[1] ?? 'Test',
            'email'      => strtolower(str_replace(' ', '_', $name)) . '_' . uniqid() . '@example.com',
            'role'       => $role,
            'password'   => 'password123',
        ]);
    }

    private function payload(): array
    {
        return [
            'wet_start' => 6,
            'wet_end' => 11,
            'active_season' => 'auto',
        ];
    }

    private function resetSettingsCache(): void
    {
        $cache = new \ReflectionProperty(SettingsHelper::class, 'cache');
        $cache->setValue(null, null);
    }
}
