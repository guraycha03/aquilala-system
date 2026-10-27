<?php

namespace Tests\Feature;

use App\Mail\AdminAccessGrantedMail;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The only in-app way to create an administrator.
 *
 * `aquilala:create-admin` is deliberately one-shot, so an existing
 * administrator grants the role to an active account instead. The point of
 * these tests is that the grant is never accidental and never silent: it
 * re-checks the granting admin's own password, demands an explicit tick,
 * refuses pending accounts, is throttled, is audit-logged with both names,
 * and tells the recipient by email.
 */
class AdminPromotionTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        Mail::fake();
    }

    private function admin(): User
    {
        return $this->admin ??= $this->makeUser('admin');
    }

    private function makeUser(string $role, string $status = User::STATUS_ACTIVE): User
    {
        $seq = uniqid();

        return User::create([
            'name' => 'Test '.ucfirst($role).' '.$seq,
            'first_name' => 'Test',
            'last_name' => ucfirst($role),
            'email' => $role.'_'.$seq.'@example.com',
            'role' => $role,
            'status' => $status,
            'password' => 'password123',
        ]);
    }

    /** @return array<string, mixed> */
    private function grantPayload(array $overrides = []): array
    {
        return array_merge([
            'password' => 'password123',
            'confirm' => '1',
        ], $overrides);
    }

    // ── The happy path ─────────────────────────────────────────────

    public function test_an_active_account_can_be_promoted_by_an_admin(): void
    {
        $target = $this->makeUser('farmer');

        $this->actingAs($this->admin())
            ->post(route('admin.users.promote', $target), $this->grantPayload())
            ->assertRedirect(route('admin.users.edit', $target))
            ->assertSessionHas('success');

        $this->assertSame('admin', $target->fresh()->role);
    }

    public function test_the_grant_is_audited_and_notifies_the_target(): void
    {
        $admin = $this->admin();
        $target = $this->makeUser('store_owner');

        $this->actingAs($admin)
            ->post(route('admin.users.promote', $target), $this->grantPayload())
            ->assertRedirect();

        $log = ActivityLog::where('type', ActivityLog::TYPE_ADMIN_PROMOTED)->firstOrFail();

        // The account that changed owns the row; the granting admin is the actor.
        $this->assertSame($target->id, $log->user_id);
        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame($target->id, $log->subject_id);
        $this->assertSame('store_owner', $log->metadata['role_before']);
        $this->assertSame('admin', $log->metadata['role_after']);
        $this->assertSame($admin->email, $log->metadata['granted_by']);

        Mail::assertSent(
            AdminAccessGrantedMail::class,
            fn (AdminAccessGrantedMail $mail) => $mail->hasTo($target->email)
        );
    }

    // ── Step-up authentication ─────────────────────────────────────

    public function test_a_wrong_password_blocks_the_grant_and_changes_nothing(): void
    {
        $admin = $this->admin();
        $target = $this->makeUser('farmer');

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $target))
            ->post(route('admin.users.promote', $target), $this->grantPayload(['password' => 'not-the-password']))
            ->assertRedirect(route('admin.users.edit', $target))
            ->assertSessionHasErrors('password');

        $this->assertSame('farmer', $target->fresh()->role);
        $this->assertSame(0, ActivityLog::where('type', ActivityLog::TYPE_ADMIN_PROMOTED)->count());
        Mail::assertNotSent(AdminAccessGrantedMail::class);
    }

    public function test_the_confirmation_tick_is_required(): void
    {
        $target = $this->makeUser('farmer');

        $this->actingAs($this->admin())
            ->post(route('admin.users.promote', $target), $this->grantPayload(['confirm' => null]))
            ->assertSessionHasErrors('confirm');

        $this->assertSame('farmer', $target->fresh()->role);
    }

    public function test_promotion_is_rate_limited_per_account(): void
    {
        $admin = $this->admin();
        $target = $this->makeUser('farmer');

        // Five wrong-password attempts are allowed per minute per account…
        foreach (range(1, 5) as $attempt) {
            $this->actingAs($admin)
                ->post(route('admin.users.promote', $target), $this->grantPayload(['password' => 'wrong-'.$attempt]))
                ->assertSessionHasErrors('password');
        }

        // …the sixth is cut off before the password is even compared, which is
        // what stops the field being brute-forced.
        $this->actingAs($admin)
            ->post(route('admin.users.promote', $target), $this->grantPayload(['password' => 'password123']))
            ->assertStatus(429);

        $this->assertSame('farmer', $target->fresh()->role);
    }

    // ── Who may grant, and to whom ─────────────────────────────────

    public function test_a_non_admin_cannot_grant_administrator_access(): void
    {
        $farmer = $this->makeUser('farmer');
        $target = $this->makeUser('farmer');

        $this->actingAs($farmer)
            ->post(route('admin.users.promote', $target), $this->grantPayload())
            ->assertForbidden();

        $this->assertSame('farmer', $target->fresh()->role);
    }

    public function test_an_account_already_admin_cannot_be_promoted_again(): void
    {
        $secondAdmin = $this->makeUser('admin');

        $this->actingAs($this->admin())
            ->from(route('admin.users.edit', $secondAdmin))
            ->post(route('admin.users.promote', $secondAdmin), $this->grantPayload())
            ->assertSessionHas('error');

        $this->assertSame(0, ActivityLog::where('type', ActivityLog::TYPE_ADMIN_PROMOTED)->count());
        Mail::assertNotSent(AdminAccessGrantedMail::class);
    }

    public function test_a_pending_account_cannot_be_promoted(): void
    {
        // An invitee who has not proved who they are yet.
        $pending = $this->makeUser('farmer', User::STATUS_PENDING);

        $this->actingAs($this->admin())
            ->from(route('admin.users.edit', $pending))
            ->post(route('admin.users.promote', $pending), $this->grantPayload())
            ->assertSessionHas('error');

        $this->assertSame('pending', $pending->fresh()->status);
        $this->assertSame('farmer', $pending->fresh()->role);
    }

    public function test_the_profile_form_cannot_set_role_to_admin(): void
    {
        $target = $this->makeUser('farmer');

        $this->actingAs($this->admin())
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'role' => 'admin',   // not one of the allowed values
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame('farmer', $target->fresh()->role);
    }

    // ── The screen itself ──────────────────────────────────────────

    public function test_the_grant_form_is_offered_only_for_non_admin_accounts(): void
    {
        $farmer = $this->makeUser('farmer');
        $otherAdmin = $this->makeUser('admin');

        $this->actingAs($this->admin())
            ->get(route('admin.users.edit', $farmer))
            ->assertOk()
            ->assertSee('Grant administrator access')
            ->assertSee('name="password"', false)
            ->assertSee('name="confirm"', false);

        $this->actingAs($this->admin())
            ->get(route('admin.users.edit', $otherAdmin))
            ->assertOk()
            ->assertDontSee('Grant administrator access')
            ->assertSee('Administrator');
    }

    public function test_the_grant_form_is_withheld_from_accounts_that_are_not_active(): void
    {
        $pending = $this->makeUser('farmer', User::STATUS_PENDING);

        $this->actingAs($this->admin())
            ->get(route('admin.users.edit', $pending))
            ->assertOk()
            ->assertDontSee('name="password"', false)
            ->assertSee('Only an active account can be granted administrator access');
    }
}
