<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubPagesStyleTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'System Admin',
            'first_name' => 'System',
            'last_name' => 'Admin',
            'email' => 'subpages_admin_'.uniqid().'@example.com',
            'role' => 'admin',
            'password' => 'password123',
        ], $overrides));
    }

    public function test_top_tab_bar_is_rendered_by_default(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('<div class="aqua-tabs"', false)
            ->assertDontSee('<li class="nav-dropdown', false);
    }

    public function test_sub_pages_render_as_dropdowns_in_sidebar_when_style_is_sidebar(): void
    {
        $admin = $this->makeAdmin(['sub_pages_style' => 'sidebar']);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertDontSee('<div class="aqua-tabs"', false)
            ->assertDontSee('<li class="tabs-zone">', false)
            ->assertDontSee('<ul class="sidebar-tabs-nav"', false)
            ->assertSee('<li class="nav-dropdown open">', false)
            ->assertSee('<button type="button" class="nav-dropdown-btn"', false)
            ->assertSee('<a href="'.route('admin.users.index').'" class="active">', false)
            ->assertSee('class="nav-dropdown-btn" aria-expanded="false"', false);
    }

    public function test_settings_tabs_style_saves_sidebar_preference(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $this->postJson(route('settings.tabs-style'), ['style' => 'sidebar'])
            ->assertOk()
            ->assertJson(['ok' => true, 'style' => 'sidebar']);

        $this->assertSame('sidebar', $admin->fresh()->sub_pages_style);
    }

    public function test_settings_tabs_style_rejects_invalid_value(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $this->postJson(route('settings.tabs-style'), ['style' => 'floating'])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        $this->assertSame('top', $admin->fresh()->sub_pages_style);
    }

    public function test_settings_tabs_style_saves_top_preference(): void
    {
        $admin = $this->makeAdmin(['sub_pages_style' => 'sidebar']);
        $this->actingAs($admin);

        $this->postJson(route('settings.tabs-style'), ['style' => 'top'])
            ->assertOk()
            ->assertJson(['ok' => true, 'style' => 'top']);

        $this->assertSame('top', $admin->fresh()->sub_pages_style);
    }
}
