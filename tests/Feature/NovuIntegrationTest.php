<?php

namespace Tests\Feature;

use App\Models\AssociationEvent;
use App\Models\Category;
use App\Models\FcmToken;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\NovuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Novu integration plumbing (in-app Inbox feed + FCM push devices):
 *
 *   - the Inbox mount renders ONLY when configured (and carries the HMAC
 *     hash only when a secret is set) — empty config changes nothing;
 *   - NovuService fires the right calls (identify / trigger / device
 *     credentials) and NEVER throws, whatever the network does;
 *   - the funnel hooks mirror the notifications pages: events reach every
 *     member, quotations reach the involved stores (anonymously);
 *   - /push/novu-token stores this device's FCM token and syncs the
 *     account's list to Novu.
 *
 * All tests Http::fake the API — nothing here touches novu.co.
 */
class NovuIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $farmer;
    private User $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin  = $this->makeUser('System Admin', 'admin');
        $this->farmer = $this->makeUser('Ana Farmer', 'farmer');
        $this->store  = $this->makeUser('Green Valley Store', 'store_owner');
    }

    private function makeUser(string $name, string $role): User
    {
        return User::create([
            'name'       => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name'  => explode(' ', $name)[1] ?? 'Test',
            'email'      => strtolower(str_replace(' ', '_', $name)) . '_' . uniqid() . '@example.com',
            'role'       => $role,
            'status'     => User::STATUS_ACTIVE,
            'password'   => 'password123',
        ]);
    }

    private function configure(array $extra = []): void
    {
        config([
            'novu.app_id'          => 'app-test-123',
            'novu.api_key'         => 'novu-secret-key',
            'novu.workflow_notice' => 'test-notice',
        ] + $extra);
    }

    private function fakeNovu(): void
    {
        Http::fake(['api.novu.co/*' => Http::response(['data' => ['acknowledged' => true]])]);
    }

    // ── Inbox mount ──────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function inbox_renders_only_when_novu_is_configured(): void
    {
        // The div only exists with config — the always-present CSS mentions
        // "novu-inbox" in class selectors, so anchor on the element id.
        $this->actingAs($this->farmer)->get(route('farmer.activity.index'))
            ->assertOk()
            ->assertDontSee('id="novu-inbox"', false)
            ->assertDontSee('AquilalaNovuPushConfig');

        $this->configure();

        $this->actingAs($this->farmer)->get(route('farmer.activity.index'))
            ->assertOk()
            ->assertSee('id="novu-inbox"', false)
            ->assertSee('app-test-123', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function guests_never_see_the_inbox(): void
    {
        $this->configure();

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('id="novu-inbox"', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function inbox_carries_the_hmac_hash_only_when_a_secret_is_set(): void
    {
        // No secret configured → no hash in the mount.
        $this->configure();
        $this->actingAs($this->farmer)->get(route('farmer.activity.index'))
            ->assertOk()
            ->assertSee('id="novu-inbox"', false)
            ->assertDontSee('subscriberHash');

        // With a secret the mount carries the HMAC proof of this user's id.
        $expected = hash_hmac('sha256', (string) $this->farmer->id, 'hmac-test-secret');
        $this->configure(['novu.hmac_secret' => 'hmac-test-secret']);

        $this->actingAs($this->farmer->fresh())->get(route('farmer.activity.index'))
            ->assertOk()
            ->assertSee('subscriberHash', false)
            ->assertSee($expected, false);
    }

    // ── NovuService ──────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function trigger_posts_the_workflow_recipients_and_payload(): void
    {
        $this->configure();
        $this->fakeNovu();

        NovuService::triggerNotice(
            'General Assembly',
            'Upcoming event: "General Assembly" on Oct 30, 2026 — Plaza',
            [$this->farmer->id, $this->store->id],
            'https://aquilala.test/farmer/calendar'
        );

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.novu.co/v1/events/trigger'
                && $request->header('Authorization')[0] === 'ApiKey novu-secret-key'
                && $request['name'] === 'test-notice'
                && $request['to'] === [
                    ['subscriberId' => (string) $this->farmer->id],
                    ['subscriberId' => (string) $this->store->id],
                ]
                && $request['payload']['title'] === 'General Assembly'
                && $request['payload']['link'] === 'https://aquilala.test/farmer/calendar'
                && str_contains($request['payload']['body'], 'Upcoming event');
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function trigger_chunks_recipients_to_the_api_limit(): void
    {
        $this->configure();
        $this->fakeNovu();

        NovuService::triggerNotice('T', 'B', range(1, 150));

        $sizes = [];
        Http::recorded(function ($request) use (&$sizes) {
            $sizes[] = count($request['to']);
        });

        $this->assertSame([100, 50], $sizes);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function identify_posts_the_subscribers_profile(): void
    {
        $this->configure();
        $this->fakeNovu();

        NovuService::identify($this->farmer);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.novu.co/v1/subscribers'
                && $request['subscriberId'] === (string) $this->farmer->id
                && $request['email'] === $this->farmer->email
                && $request['firstName'] === 'Ana';
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function without_configuration_no_api_call_is_made(): void
    {
        Http::fake();

        NovuService::triggerNotice('T', 'B', [1]);
        NovuService::identify($this->farmer);
        NovuService::setDeviceTokens('1', ['token']);

        Http::assertNothingSent();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unreachable_novu_never_throws(): void
    {
        $this->configure();
        Http::fake(['api.novu.co/*' => fn () => throw new ConnectionException('connection refused')]);

        NovuService::triggerNotice('T', 'B', [1]);
        NovuService::identify($this->farmer);
        NovuService::setDeviceTokens('1', ['token']);

        $this->assertTrue(true); // surviving the three calls is the assertion
    }

    // ── Funnel hooks (mirror the notifications pages) ────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function an_event_creation_feeds_every_member_into_the_novu_trigger(): void
    {
        $this->configure();
        $this->fakeNovu();

        $event = AssociationEvent::create([
            'title'      => 'Free Corn Seeds Distribution',
            'event_type' => 'free_input',
            'event_date' => now()->addDays(3)->toDateString(),
            'location'   => 'Association Warehouse',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);
        ActivityLogger::eventCreated($event);

        Http::assertSentCount(1); // VAPID push is a no-op in tests; feed only
        Http::assertSent(function ($request) {
            $recipients = collect($request['to'])->pluck('subscriberId');

            return $request->url() === 'https://api.novu.co/v1/events/trigger'
                && $request['payload']['title'] === 'Free Corn Seeds Distribution'
                && $recipients->contains((string) $this->farmer->id)
                && $recipients->contains((string) $this->store->id)
                && !$recipients->contains((string) $this->admin->id)
                && str_contains($request['payload']['link'], 'calendar');
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_quotation_feeds_only_the_involved_store_and_stays_anonymous(): void
    {
        $this->configure();
        $this->fakeNovu();

        $quotation = $this->makeQuotation($this->farmer);

        $this->actingAs($this->farmer);
        ActivityLogger::quotationCreated($quotation);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.novu.co/v1/events/trigger'
                && $request['payload']['title'] === 'New cost estimate'
                && $request['to'] === [['subscriberId' => (string) $this->store->id]]
                && str_contains($request['payload']['link'], 'quotations')
                && !str_contains($request['payload']['body'], 'Ana Farmer');
        });
    }

    // ── Login identifies the subscriber ──────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function signing_in_upserts_the_novu_subscriber(): void
    {
        $this->configure();
        $this->fakeNovu();

        $this->post('/login', [
            'email'    => $this->farmer->email,
            'password' => 'password123',
        ])->assertRedirect(route('dashboard.redirect', ['role' => 'farmer']));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.novu.co/v1/subscribers'
                && $request['subscriberId'] === (string) $this->farmer->id;
        });
    }

    // ── FCM device tokens (/push/novu-token) ─────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function the_token_endpoint_stores_the_device_and_syncs_novu_credentials(): void
    {
        $this->configure(['novu.firebase.api_key' => 'fb-web-key']);
        $this->fakeNovu();

        $this->actingAs($this->farmer)
            ->postJson('/push/novu-token', ['token' => 'fcm-token-abc'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $this->farmer->id,
            'token'   => 'fcm-token-abc',
        ]);

        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && $request->url() === 'https://api.novu.co/v1/subscribers/' . $this->farmer->id . '/credentials'
                && $request['providerId'] === 'fcm'
                && $request['credentials']['deviceTokens'] === ['fcm-token-abc'];
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revoking_drops_this_devices_token_and_resyncs(): void
    {
        $this->configure(['novu.firebase.api_key' => 'fb-web-key']);
        $this->fakeNovu();

        $this->actingAs($this->farmer)
            ->postJson('/push/novu-token', ['token' => 'fcm-token-abc'])
            ->assertOk();

        $this->actingAs($this->farmer)
            ->postJson('/push/novu-token', ['token' => 'fcm-token-abc', 'revoke' => true])
            ->assertOk();

        $this->assertDatabaseMissing('fcm_tokens', ['token' => 'fcm-token-abc']);

        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && $request['credentials']['deviceTokens'] === []);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function the_token_endpoint_requires_auth_and_configuration(): void
    {
        $this->postJson('/push/novu-token', ['token' => 'x'])->assertStatus(401);

        // Signed in, but Novu/Firebase not configured → no row, no call.
        $this->actingAs($this->farmer)
            ->postJson('/push/novu-token', ['token' => 'x'])
            ->assertOk()
            ->assertJson(['ok' => false, 'reason' => 'not-configured']);

        $this->assertDatabaseCount('fcm_tokens', 0);
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function makeQuotation(User $farmer): Quotation
    {
        $category = Category::create([
            'name'        => 'Fertilizers',
            'slug'        => 'fertilizers-' . uniqid(),
            'description' => 'Fertilizer inputs',
        ]);

        $product = Product::create([
            'user_id'           => $this->store->id,
            'category_id'       => $category->id,
            'category'          => $category->name,
            'sku'               => 'SKU-NOVU-' . uniqid(),
            'name'              => 'Premium Urea',
            'price'             => 100,
            'price_unit'        => 'sack',
            'stock_quantity'    => 20,
            'stock_unit'        => 'sacks',
            'reorder_threshold' => 5,
            'status'            => 'active',
        ]);

        $quotation = Quotation::create([
            'user_id'              => $farmer->id,
            'quotation_number'     => 'QT-NOVU-TEST',
            'crop_type'            => 'Rice',
            'season'               => 'wet',
            'land_area'            => 1.5,
            'estimated_total_cost' => 300.00,
            'status'               => Quotation::STATUS_COMPUTED,
            'valid_until'          => now()->addDays(14),
            'notes'                => null,
        ]);

        $quotation->items()->create([
            'product_id'          => $product->id,
            'product_name'        => $product->name,
            'price_at_generation' => 100,
            'calculated_quantity' => 3,
            'subtotal'            => 300,
        ]);

        return $quotation;
    }
}
