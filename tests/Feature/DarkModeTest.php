<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DarkModeTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $name, string $role): User
    {
        return User::create([
            'name' => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? 'Test',
            'email' => strtolower(str_replace(' ', '_', $name)).'_'.uniqid().'@example.com',
            'role' => $role,
            'password' => 'password123',
        ]);
    }

    // ── Saving the preference ──────────────────────────────────────────────

    public function test_every_role_can_save_a_dark_theme(): void
    {
        foreach (['admin', 'farmer', 'store_owner'] as $i => $role) {
            $user = $this->makeUser("Theme User{$i}", $role);

            $this->actingAs($user)
                ->postJson(route('settings.theme'), ['mode' => 'dark'])
                ->assertOk()
                ->assertJson(['ok' => true, 'mode' => 'dark']);

            $this->assertSame('dark', $user->fresh()->theme_mode, "Role {$role} should persist dark mode.");
        }
    }

    public function test_light_mode_can_be_restored(): void
    {
        $user = $this->makeUser('Theme Toggle', 'admin');
        $user->update(['theme_mode' => 'dark']);

        $this->actingAs($user)
            ->postJson(route('settings.theme'), ['mode' => 'light'])
            ->assertOk()
            ->assertJson(['ok' => true, 'mode' => 'light']);

        $this->assertSame('light', $user->fresh()->theme_mode);
    }

    public function test_an_invalid_theme_is_rejected(): void
    {
        $user = $this->makeUser('Theme Bad', 'farmer');

        $this->actingAs($user)
            ->postJson(route('settings.theme'), ['mode' => 'sepia'])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        $this->assertSame('light', $user->fresh()->theme_mode);
    }

    public function test_guests_cannot_save_a_theme(): void
    {
        $this->post(route('settings.theme'), ['mode' => 'dark'])
            ->assertRedirect(route('login'));
    }

    // ── Rendering consistently ─────────────────────────────────────────────

    public function test_new_accounts_render_in_light_mode_by_default(): void
    {
        $admin = $this->makeUser('Theme Default', 'admin');

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('<html lang="en" data-theme="light"', false);
    }

    public function test_a_saved_dark_theme_appears_on_every_page(): void
    {
        $admin = $this->makeUser('Theme Dark', 'admin');
        $admin->update(['theme_mode' => 'dark']);

        foreach ([route('dashboard.admin'), route('admin.users.index'), route('admin.calendar.index')] as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('<html lang="en" data-theme="dark"', false);
        }
    }

    public function test_the_dark_stylesheet_is_linked_from_the_layout(): void
    {
        $admin = $this->makeUser('Theme Css', 'admin');

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('css/dark-mode.css');
    }

    // ── Settings UI for every role ─────────────────────────────────────────

    public function test_every_settings_page_offers_the_display_theme_picker(): void
    {
        $cases = [
            ['admin', route('admin.settings.interface'), 'Display Theme'],
            ['farmer', route('farmer.settings.index'), 'Display theme'],
            ['store_owner', route('store.settings.index'), 'Display theme'],
        ];

        foreach ($cases as $i => [$role, $url, $heading]) {
            $user = $this->makeUser("Settings User{$i}", $role);

            $this->actingAs($user)
                ->get($url)
                ->assertOk()
                ->assertSee($heading)
                ->assertSee('data-tmp-mode="dark"', false);
        }
    }

    // ── Side menu color is locked in dark mode ─────────────────────────────

    public function test_side_menu_color_can_be_saved_in_light_mode(): void
    {
        $user = $this->makeUser('Sidebar Light', 'admin');

        $this->actingAs($user)
            ->postJson(route('settings.ui'), ['theme' => 'matcha'])
            ->assertOk()
            ->assertJson(['ok' => true, 'theme' => 'matcha']);

        $this->assertSame('matcha', $user->fresh()->sidebar_theme);
    }

    public function test_side_menu_color_is_locked_in_dark_mode(): void
    {
        $user = $this->makeUser('Sidebar Dark', 'admin');
        $user->update(['theme_mode' => 'dark', 'sidebar_theme' => 'emerald']);

        $this->actingAs($user)
            ->postJson(route('settings.ui'), ['theme' => 'matcha'])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        $this->assertSame('emerald', $user->fresh()->sidebar_theme);
    }

    public function test_dark_mode_renders_the_default_side_menu_color(): void
    {
        $user = $this->makeUser('Sidebar Default', 'farmer');
        $user->update(['theme_mode' => 'dark', 'sidebar_theme' => 'khaki']);

        $default = config('sidebar.default');

        $this->actingAs($user)
            ->get(route('dashboard.farmer'))
            ->assertOk()
            ->assertSee('<body data-sidebar-theme="'.$default.'"', false)
            ->assertDontSee('<body data-sidebar-theme="khaki"', false);
    }
}
