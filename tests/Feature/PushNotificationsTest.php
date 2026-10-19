<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\PushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Web Push plumbing: the VAPID public key endpoint, device subscribe /
 * unsubscribe, and the golden rule shared with the AI layer — with no keys
 * configured (the default in tests) push must be a total no-op and never
 * break the request that touched it.
 */
class PushNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $farmer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->farmer = User::create([
            'name' => 'Ana Farmer',
            'first_name' => 'Ana',
            'last_name' => 'Farmer',
            'email' => 'push_farmer_' . uniqid() . '@example.com',
            'role' => 'farmer',
            'password' => 'password123',
        ]);
    }

    // ── Public key ──────────────────────────────────────────────────────

    public function test_the_public_key_is_served_when_push_is_configured(): void
    {
        config()->set('push.vapid_public_key', 'TEST_PUBLIC_KEY');
        config()->set('push.vapid_private_key', 'TEST_PRIVATE_KEY');

        $this->actingAs($this->farmer)
            ->get(route('push.public-key'))
            ->assertOk()
            ->assertJson(['key' => 'TEST_PUBLIC_KEY']);
    }

    public function test_the_public_key_is_empty_when_push_is_not_configured(): void
    {
        // phpunit.xml forces the VAPID env keys empty — the unconfigured state.
        $this->actingAs($this->farmer)
            ->get(route('push.public-key'))
            ->assertOk()
            ->assertJson(['key' => null]);
    }

    public function test_guests_cannot_fetch_the_public_key(): void
    {
        $this->get(route('push.public-key'))->assertRedirect(route('login'));
    }

    // ── Subscribe / unsubscribe ─────────────────────────────────────────

    public function test_a_signed_in_user_can_store_a_push_subscription(): void
    {
        $this->actingAs($this->farmer)
            ->postJson(route('push.subscribe'), $this->payload())
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->farmer->id,
            'endpoint' => 'https://push.example.com/endpoint-1',
            'p256dh' => 'test-p256dh-key',
            'auth' => 'test-auth-key',
        ]);
    }

    public function test_re_subscribing_the_same_endpoint_updates_instead_of_duplicating(): void
    {
        $this->actingAs($this->farmer)->postJson(route('push.subscribe'), $this->payload());
        $this->actingAs($this->farmer)->postJson(route('push.subscribe'), $this->payload());

        $this->assertSame(1, PushSubscription::count());
    }

    public function test_a_user_can_unsubscribe_their_own_device(): void
    {
        $this->actingAs($this->farmer)->postJson(route('push.subscribe'), $this->payload());

        $this->actingAs($this->farmer)
            ->postJson(route('push.unsubscribe'), ['endpoint' => 'https://push.example.com/endpoint-1'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(0, PushSubscription::count());
    }

    public function test_a_user_cannot_unsubscribe_another_users_endpoint(): void
    {
        $this->actingAs($this->farmer)->postJson(route('push.subscribe'), $this->payload());

        $other = User::create([
            'name' => 'Other Farmer',
            'first_name' => 'Other',
            'last_name' => 'Farmer',
            'email' => 'push_other_' . uniqid() . '@example.com',
            'role' => 'farmer',
            'password' => 'password123',
        ]);

        $this->actingAs($other)
            ->postJson(route('push.unsubscribe'), ['endpoint' => 'https://push.example.com/endpoint-1'])
            ->assertOk();

        // The original owner's row survives.
        $this->assertSame(1, PushSubscription::count());
    }

    public function test_invalid_subscribe_payloads_are_rejected(): void
    {
        $this->actingAs($this->farmer)
            ->postJson(route('push.subscribe'), ['endpoint' => 'not-a-url'])
            ->assertStatus(422);

        $this->assertSame(0, PushSubscription::count());
    }

    public function test_guests_cannot_subscribe(): void
    {
        $this->postJson(route('push.subscribe'), $this->payload())
            ->assertStatus(401);

        $this->assertSame(0, PushSubscription::count());
    }

    // ── Fail-safe: never breaks the system ──────────────────────────────

    public function test_push_is_unavailable_without_vapid_keys(): void
    {
        // The phpunit default: keys forced empty.
        $this->assertFalse(PushService::available());
        $this->assertNull(PushService::publicKey());
    }

    public function test_sending_without_keys_is_a_silent_no_op(): void
    {
        // Even with a stored subscription, no keys → nothing happens, no error.
        $this->actingAs($this->farmer)->postJson(route('push.subscribe'), $this->payload());

        PushService::sendToUsers([$this->farmer->id], 'Title', 'Body', '/farmer/calendar');

        $this->assertSame(1, PushSubscription::count());
    }

    public function test_the_settings_page_offers_the_toggle_only_when_configured(): void
    {
        // Unconfigured: no push toggle on the Settings page.
        $this->actingAs($this->farmer)
            ->get(route('farmer.settings.index'))
            ->assertOk()
            ->assertDontSee('id="push-row"', false);

        config()->set('push.vapid_public_key', 'K');
        config()->set('push.vapid_private_key', 'K');

        $this->actingAs($this->farmer)
            ->get(route('farmer.settings.index'))
            ->assertOk()
            ->assertSee('id="push-row"', false)
            ->assertSee('Push notifications');
    }

    public function test_the_settings_page_always_links_to_activity_history(): void
    {
        $this->actingAs($this->farmer)
            ->get(route('farmer.settings.index'))
            ->assertOk()
            ->assertSee('Activity history')
            ->assertSee(route('farmer.activity.index'), false);

        // Same partial on every role's Settings page.
        $store = User::create([
            'name' => 'Sam Store', 'first_name' => 'Sam', 'last_name' => 'Store',
            'email' => 'push_store_' . uniqid() . '@example.com',
            'role' => 'store_owner', 'password' => 'password123',
        ]);
        $admin = User::create([
            'name' => 'Ada Admin', 'first_name' => 'Ada', 'last_name' => 'Admin',
            'email' => 'push_admin_' . uniqid() . '@example.com',
            'role' => 'admin', 'password' => 'password123',
        ]);

        $this->actingAs($store)
            ->get(route('store.settings.index'))
            ->assertOk()
            ->assertSee('Activity history')
            ->assertSee(route('store.activity.index'), false);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Activity history')
            ->assertSee(route('admin.activity.index'), false);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'endpoint' => 'https://push.example.com/endpoint-1',
            'keys' => [
                'p256dh' => 'test-p256dh-key',
                'auth' => 'test-auth-key',
            ],
        ], $overrides);
    }
}
