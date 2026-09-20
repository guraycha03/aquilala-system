<?php

namespace Tests;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require Application::inferBasePath() . '/bootstrap/app.php';

        $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));

        $app->make(Kernel::class)->bootstrap();

        // Guard: force every test onto an isolated in-memory SQLite database,
        // even when `php artisan config:cache` is active. With cached config the
        // phpunit.xml <env> overrides are ignored, so RefreshDatabase would run
        // migrate:fresh against the real MySQL database (aquilala_db) and wipe
        // every table. Forcing the connection here keeps tests hermetic no
        // matter how the application was booted.
        if ($app->config instanceof Repository) {
            $app->config->set('database.default', 'sqlite');
            $app->config->set('database.connections.sqlite.database', ':memory:');
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests run against the real web middleware (including any
        // cached config from `php artisan config:cache`), which re-enables CSRF
        // protection and 419s every POST. CSRF is a browser-session concern, not
        // something feature tests verify, so disable it explicitly.
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }
}