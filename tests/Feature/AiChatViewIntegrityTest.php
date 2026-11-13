<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * The AI chat page has broken three separate times in ways that were
 * invisible until someone opened a browser, and each time the cause was
 * structural rather than logical:
 *
 *   1. A duplicate `store/ai-chat/index.blade.php` existed that nothing
 *      rendered. AiChatController::index() always renders the shared
 *      `farmer.ai-chat.index`, so edits to the store copy silently did
 *      nothing — a whole session of work landed in a dead file.
 *
 *   2. A single invalid JavaScript token (`'''`) inside an inline <script>
 *      block aborted the ENTIRE block. The page still rendered, the chat
 *      still sent messages, but the copy handlers, the suggestion chips and
 *      the product-card renderer never ran — and nothing reported an error.
 *
 *   3. A stale Blade/view cache made correct edits appear not to apply, so
 *      the same fix was re-applied repeatedly.
 *
 * These tests turn all three into a red build instead of a mystery.
 */
class AiChatViewIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /** Roles that get their own route into the shared chat view. */
    private const ROLES = [
        'farmer' => 'farmer.ai-chat.index',
        'store_owner' => 'store.ai-chat.index',
        'admin' => 'admin.ai-chat.index',
    ];

    /**
     * Guards trap #1: a view file that nothing renders is worse than a
     * missing one, because edits to it vanish silently. Every AI chat view
     * on disk must be reachable from an actual render call.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function no_ai_chat_view_is_orphaned(): void
    {
        $views = glob(resource_path('views/*/*/index.blade.php')) ?: [];
        $views = array_values(array_filter($views, fn ($path) => str_contains($this->slashes($path), '/ai-chat/')));

        $this->assertNotEmpty(
            $views,
            'No AI chat view found at resources/views/*/ai-chat/index.blade.php — the shared view was moved or deleted.'
        );

        $sources = $this->allPhpSources();

        foreach ($views as $path) {
            $dotted = $this->dottedViewName($path);

            $referenced = false;

            foreach ($sources as $source) {
                $code = file_get_contents($source);

                // Match an actual render call. Deliberately NOT a bare mention
                // of the name: `route('store.ai-chat.index')` is a route name,
                // and that is exactly what masked this bug for so long.
                if (preg_match(
                    '/(?:view\s*\(\s*|View::make\s*\(\s*|@include\s*\(\s*|@extends\s*\(\s*)[\x27"]' . preg_quote($dotted, '/') . '[\x27"]/',
                    $code
                )) {
                    $referenced = true;

                    break;
                }
            }

            $this->assertTrue(
                $referenced,
                "resources/views/{$dotted}.blade.php is rendered by nothing.\n"
                . "Edits to it will never appear in the browser — AiChatController::index() renders "
                . "`farmer.ai-chat.index` for every role.\n"
                . "Either wire it up with a real view('{$dotted}') call, or delete the file."
            );
        }
    }

    /**
     * Guards trap #2: one bad token kills a whole <script> block, and the
     * page keeps working well enough that nobody notices. Every inline
     * script the chat page actually ships must parse.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function every_role_ships_javascript_that_parses(): void
    {
        foreach (self::ROLES as $role => $route) {
            $html = $this->chatPage($role, $route);

            $checked = 0;

            foreach ($this->inlineScripts($html) as $script) {
                $checked++;

                $error = $this->nodeCheck($script);

                $this->assertNull(
                    $error,
                    "The {$role} chat page ships a <script> block that does not parse.\n"
                    . "A single bad token aborts the whole block, so EVERY handler inside it silently stops "
                    . "running — copy buttons, suggestion chips and product cards all die at once, while the "
                    . "page still looks fine.\n\n{$error}"
                );
            }

            $this->assertGreaterThan(
                0,
                $checked,
                "The {$role} chat page rendered no inline JavaScript at all — the view is wrong or the "
                . "script block was lost."
            );
        }
    }

    /**
     * Guards trap #3 and the dead-file trap together: assert on the HTML the
     * browser really receives, so a stale cache or a wrong view file fails
     * here rather than in a manual check.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function every_role_renders_the_shared_view_with_its_features(): void
    {
        foreach (self::ROLES as $role => $route) {
            $html = $this->chatPage($role, $route);

            // The copy button lives below each bubble, and the tile index is
            // what lets a named product become a real product card. Both only
            // exist in the shared view — their absence means the wrong file
            // rendered.
            foreach (['class="copy-btn"', 'page-link', 'indexProducts'] as $feature) {
                $this->assertStringContainsString(
                    $feature,
                    $html,
                    "The {$role} chat page is missing `{$feature}`, so it did not render the shared "
                    . "farmer.ai-chat/index view. Check AiChatController::index() and clear the view cache "
                    . "with `php artisan view:clear`."
                );
            }

            // Page links must never be drawn as product cards: a page has no
            // product image, and the placeholder it used to get was wrong.
            $this->assertStringNotContainsString(
                'via.placeholder.com',
                $html,
                "The {$role} chat page still emits a placeholder product image. A markdown link in a reply "
                . "is a page route, not a product — it must render as a plain .page-link."
            );
        }
    }

    /** The chat page HTML for a role, asserting it renders at all. */
    private function chatPage(string $role, string $route): string
    {
        $user = $this->makeUser('Integrity ' . ucfirst($role), $role);

        $response = $this->actingAs($user)->get(route($route));

        $response->assertOk();

        return $response->getContent();
    }

    /** Inline script bodies, skipping external and JSON-island blocks. */
    private function inlineScripts(string $html): array
    {
        preg_match_all('#<script(\s[^>]*)?>(.*?)</script>#si', $html, $matches, PREG_SET_ORDER);

        $scripts = [];

        foreach ($matches as $match) {
            $attrs = (string) ($match[1] ?? '');

            // External: the JavaScript lives in src, not in the body.
            if (str_contains($attrs, 'src=')) {
                continue;
            }

            // Data islands (application/json, ld+json) are not JavaScript.
            if (preg_match('/type\s*=\s*[\x27"]([^\x27"]+)[\x27"]/', $attrs, $type)
                && ! in_array(strtolower($type[1]), ['text/javascript', 'application/javascript', 'module'], true)
            ) {
                continue;
            }

            if (trim($match[2]) !== '') {
                $scripts[] = $match[2];
            }
        }

        return $scripts;
    }

    /** null when the script parses, otherwise node's explanation. */
    private function nodeCheck(string $script): ?string
    {
        $file = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . uniqid('ai-chat-js-', true) . '.js';
        file_put_contents($file, $script);

        $process = new Process(['node', '--check', $file]);
        $process->setTimeout(30);
        $process->run();

        @unlink($file);

        if ($process->isSuccessful()) {
            return null;
        }

        return trim($process->getErrorOutput()) !== ''
            ? trim($process->getErrorOutput())
            : trim($process->getOutput());
    }

    private function makeUser(string $name, string $role): User
    {
        return User::create([
            'name' => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? 'Test',
            'email' => strtolower(str_replace(' ', '_', $name)) . '_' . uniqid() . '@example.com',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
            'password' => 'password123',
        ]);
    }

    /** resources/views/farmer/ai-chat/index.blade.php -> farmer.ai-chat.index */
    private function dottedViewName(string $path): string
    {
        $path = $this->slashes($path);
        $path = substr($path, strpos($path, '/views/') + 7);
        $path = preg_replace('/\.blade\.php$/', '', $path);

        return str_replace('/', '.', $path);
    }

    private function slashes(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /** Every PHP file that could contain a render call. */
    private function allPhpSources(): array
    {
        $files = [];

        foreach (['app', 'routes', 'config', 'resources', 'tests', 'database'] as $dir) {
            $base = base_path($dir);

            if (! is_dir($base)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
