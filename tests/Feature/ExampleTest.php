<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * Public self-registration is disabled, so guests are shown the sign-in
     * page — and it must be served at the root URL (the canonical, indexable
     * landing page), not behind a redirect.
     */
    public function test_guests_see_the_sign_in_landing_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Sign in to access your dashboard.')
            ->assertSee('index, follow', false);
    }

    public function test_the_login_url_still_works_for_bookmarks(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in to access your dashboard.');
    }

    /**
     * The landing page carries the full SEO/social/entity payload.
     */
    public function test_the_landing_page_carries_its_seo_tags(): void
    {
        $response = $this->get('/');
        $html = $response->getContent();

        // SERP snippet
        $response->assertSee('<title>Aquilala Irrigators Association Inc. — Sign In</title>', false);
        $response->assertSee('name="description" content="Official member portal of the Aquilala Irrigators Association Inc. (Aqui lala)', false);
        $response->assertSee('name="keywords" content="Aquilala, Aqui lala, Aquilala Irrigators', false);
        $response->assertSee('name="robots" content="index, follow"', false);
        $response->assertSee('<link rel="canonical" href="' . url('/') . '">', false);

        // Favicons
        $response->assertSee('rel="icon" href="' . asset('favicon.ico') . '"', false);
        $response->assertSee('rel="apple-touch-icon"', false);

        // Open Graph + Twitter card
        $response->assertSee('property="og:title" content="Aquilala Irrigators Association Inc. — Sign In"', false);
        $response->assertSee('property="og:image" content="' . asset('assets/og-banner.png') . '"', false);
        $response->assertSee('property="og:url" content="' . url('/') . '"', false);
        $response->assertSee('name="twitter:card" content="summary_large_image"', false);

        // Schema.org entity mapping (typo/partial-name resilience)
        $response->assertSee('application/ld+json', false);
        $this->assertStringContainsString('"@type":"Organization"', $html);
        $this->assertStringContainsString('"name":"Aquilala Irrigators Association Inc."', $html);
        $this->assertStringContainsString('"alternateName":["Aquilala","Aqui lala","Aquilala Irrigators"', $html);
        $this->assertStringContainsString('"addressLocality":"Bulan"', $html);
        $this->assertStringContainsString('"addressRegion":"Sorsogon"', $html);
        $this->assertStringContainsString('"addressCountry":"PH"', $html);
    }

    /**
     * Utility auth screens (activation, password reset, …) must never be
     * indexed — only the landing page opts in.
     */
    public function test_other_auth_pages_stay_noindex(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false);

        $this->get('/activate')
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false);
    }

    /**
     * The authenticated app shell is never indexable either.
     */
    public function test_the_app_layout_is_noindex(): void
    {
        $this->assertStringContainsString(
            '<meta name="robots" content="noindex, nofollow">',
            file_get_contents(resource_path('views/layouts/sidebar.blade.php'))
        );
    }
}
