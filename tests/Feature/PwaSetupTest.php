<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Progressive Web App setup: the manifest, service worker, offline page and
 * registration script exist as static files, the manifest is valid and its
 * icons resolve, and both layouts wire up the install/offline tags. None of
 * this touches existing features — it only makes the system installable to a
 * device home screen like an app.
 */
class PwaSetupTest extends TestCase
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

    public function test_the_manifest_is_valid_and_points_at_existing_icons(): void
    {
        $path = public_path('manifest.json');
        $this->assertFileExists($path);

        $manifest = json_decode(file_get_contents($path), true);
        $this->assertIsArray($manifest, 'manifest.json must be valid JSON');
        $this->assertSame(JSON_ERROR_NONE, json_last_error());

        foreach (['name', 'short_name', 'start_url', 'scope', 'display', 'background_color', 'theme_color'] as $key) {
            $this->assertNotEmpty($manifest[$key] ?? null, "manifest is missing '{$key}'");
        }

        $this->assertNotEmpty($manifest['icons'] ?? []);
        $sizes = [];
        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')), "icon {$icon['src']} missing");
            $sizes[$icon['sizes']] = true;
        }
        $this->assertArrayHasKey('192x192', $sizes, 'manifest needs a 192px icon');
        $this->assertArrayHasKey('512x512', $sizes, 'manifest needs a 512px icon');
    }

    public function test_the_service_worker_and_support_files_exist(): void
    {
        foreach (['sw.js', 'offline.html', 'js/pwa-register.js'] as $file) {
            $this->assertFileExists(public_path($file), "public/{$file} missing");
        }

        $sw = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString("addEventListener('install'", $sw);
        $this->assertStringContainsString("addEventListener('fetch'", $sw);
        $this->assertStringContainsString("addEventListener('activate'", $sw);
        $this->assertStringContainsString('CLEAR_PAGES', $sw, 'logout must clear cached pages');

        $register = file_get_contents(public_path('js/pwa-register.js'));
        $this->assertStringContainsString("register('/sw.js')", $register);
        $this->assertStringContainsString('CLEAR_PAGES', $register);

        $offline = file_get_contents(public_path('offline.html'));
        $this->assertStringContainsString("You're offline", $offline);
    }

    public function test_the_login_page_carries_the_pwa_tags(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('js/pwa-register.js', false)
            ->assertSee('apple-mobile-web-app-capable', false)
            ->assertSee('apple-touch-icon', false)
            ->assertSee('theme-color', false);
    }

    public function test_the_app_layout_carries_the_pwa_tags(): void
    {
        $farmer = $this->makeUser('PWA Farmer', 'farmer');

        $this->actingAs($farmer)
            ->get(route('dashboard.farmer'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('js/pwa-register.js', false)
            ->assertSee('apple-mobile-web-app-capable', false)
            ->assertSee('images/pwa/apple-touch-icon.png', false)
            ->assertSee('theme-color', false);
    }

    // ── Connection status: a strip at the top, never a dead-end page ──

    public function test_the_connection_strip_is_wired_into_both_layouts(): void
    {
        $this->assertFileExists(public_path('js/net-status.js'), 'public/js/net-status.js missing');

        $js = file_get_contents(public_path('js/net-status.js'));
        $this->assertStringContainsString("fetch('/up'", $js, 'the strip must probe the real server, not just navigator.onLine');
        $this->assertStringContainsString('AQ_NET', $js, 'the strip must listen to the service worker');
        $this->assertStringContainsString('aq-netbar-on', $js, 'the strip must offset the fixed chrome instead of covering it');
        $this->assertStringContainsString('turbo:load', $js, 'Turbo swaps <body>, so the strip has to be re-attached');

        // Both shells: the signed-in app and the login page behind it.
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('js/net-status.js', false);

        $farmer = $this->makeUser('PWA Strip Farmer', 'farmer');

        $this->actingAs($farmer)
            ->get(route('dashboard.farmer'))
            ->assertOk()
            ->assertSee('js/net-status.js', false);
    }

    public function test_the_health_endpoint_the_strip_probes_is_live(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_the_offline_page_recovers_on_its_own(): void
    {
        $offline = file_get_contents(public_path('offline.html'));

        $this->assertStringContainsString("You're offline", $offline);
        $this->assertStringContainsString(
            "fetch('/up'",
            $offline,
            'the offline page must probe the server rather than assume the internet is the problem'
        );
        $this->assertStringContainsString(
            'location.reload()',
            $offline,
            '...and reload itself when the server answers, without the user having to work out what is wrong'
        );
        $this->assertStringContainsString(
            "addEventListener('online'",
            $offline,
            '...the moment connectivity returns'
        );
    }

    public function test_the_service_worker_retries_before_declaring_the_app_offline(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString(
            'PAGE_RETRY_DELAY_MS',
            $sw,
            'one transient failure must not drop a working session into the offline screen'
        );
        $this->assertStringContainsString(
            'AQ_NET',
            $sw,
            'the open page must be told the moment a navigation fails'
        );
        $this->assertStringContainsString('cachePage', $sw);
    }
}
