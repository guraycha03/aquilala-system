<?php

namespace Tests\Feature;

use App\Mail\ActivationOtpMail;
use App\Mail\AdminCreationKeyMail;
use App\Models\ActivityLog;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The Create Account page as an account-type chooser.
 *
 * The first decision is what kind of account it is, then each type has its
 * own form:
 *
 *   - farmer       -> the member picker (made *from* a member record)
 *   - store owner  -> handed to the Stores page, which owns that form
 *   - admin        -> typed in from scratch, behind a password re-check,
 *                     an explicit consent box, a throttle and an audit row
 */
class AdminAccountTypeChooserTest extends TestCase
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
        return $this->admin ??= User::create([
            'name' => 'System Admin',
            'first_name' => 'System',
            'last_name' => 'Admin',
            'email' => 'chooser_admin_'.uniqid().'@example.com',
            'role' => 'admin',
            'password' => 'password123',
        ]);
    }

    private function member(array $overrides = []): Member
    {
        return Member::create(array_merge([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'name' => 'Juan Dela Cruz',
            'email' => 'juan.delacruz@example.com',
            'phone' => '09171234567',
            'status' => Member::STATUS_ACTIVE,
        ], $overrides));
    }

    private function validAdminPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Miguel',
            'middle_initial' => 'R',
            'last_name' => 'Santos',
            'email' => 'miguel.admin@example.com',
            'phone' => '09171234567',
            'password' => 'password123',
            'confirm' => '1',
        ], $overrides);
    }

    private function unlockAdminCreation(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.requestAdminCreationKey'), [
                'password' => 'password123',
            ])
            ->assertRedirect();

        $keyMail = Mail::sent(AdminCreationKeyMail::class)->last();

        $this->actingAs($this->admin())
            ->post(route('admin.users.verifyAdminCreationKey'), [
                'key' => $keyMail->key,
            ])
            ->assertRedirect(route('admin.users.create', ['type' => 'admin']));
    }

    // ── The chooser itself ────────────────────────────────────────

    #[Test]
    public function test_the_create_page_opens_with_a_type_chooser(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.users.create'));

        $response->assertOk()
            ->assertSee('Create Account')
            ->assertSee('Farmer Account')
            ->assertSee('Store Owner Account')
            ->assertSee('Administrator Account')
            ->assertSee(route('admin.users.create', ['type' => 'farmer']), false)
            ->assertSee(route('admin.stores.create'), false)
            ->assertSee(route('admin.users.create', ['type' => 'admin']), false)
            // The bare page is the chooser, not the old member picker.
            ->assertDontSee('Choose a Member');
    }

    #[Test]
    public function test_the_farmer_choice_opens_the_member_picker(): void
    {
        $waiting = $this->member();

        $this->actingAs($this->admin())
            ->get(route('admin.users.create', ['type' => 'farmer']))
            ->assertOk()
            ->assertSee('Choose a Member')
            ->assertSee($waiting->fullName());
    }

    #[Test]
    public function test_the_store_owner_choice_hands_over_to_the_store_form(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.users.create', ['type' => 'store_owner']))
            ->assertRedirect(route('admin.stores.create'));
    }

    // ── The administrator form ────────────────────────────────────

    #[Test]
    public function test_the_administrator_choice_opens_the_typed_form(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.users.create', ['type' => 'admin']))
            ->assertOk()
            ->assertSee('Verify administrator creation')
            ->assertSee('Email Security Key');

        $this->unlockAdminCreation();

        $this->actingAs($this->admin())
            ->get(route('admin.users.create', ['type' => 'admin']))
            ->assertOk()
            ->assertSee('Create an Administrator Account')
            ->assertSee(route('admin.users.storeAdmin'), false)
            ->assertSee('Your Password')
            ->assertSee('I understand this account will have full control')
            ->assertSee('name="confirm"', false);
    }

    #[Test]
    public function test_an_administrator_account_is_created_behind_the_step_up_check(): void
    {
        $this->unlockAdminCreation();

        $this->actingAs($this->admin())
            ->post(route('admin.users.storeAdmin'), $this->validAdminPayload())
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $user = User::where('email', 'miguel.admin@example.com')->firstOrFail();

        $this->assertSame('admin', $user->role);
        $this->assertSame(User::STATUS_PENDING, $user->status);
        $this->assertSame('Miguel', $user->first_name);
        $this->assertSame('Santos', $user->last_name);
        $this->assertSame('+639171234567', $user->phone);
        $this->assertStringStartsWith('$2y$', $user->password, 'no usable password exists yet — the code does');

        // The activation code went to the new administrator.
        Mail::assertSent(ActivationOtpMail::class, fn ($mail) => $mail->hasTo('miguel.admin@example.com'));

        // The privilege audit row names both sides: subject = the new
        // admin, actor = the administrator who created the account.
        $this->assertTrue(
            ActivityLog::where('type', ActivityLog::TYPE_ADMIN_CREATED)
                ->where('subject_id', $user->id)
                ->where('actor_id', $this->admin->id)
                ->exists(),
            'The audit row must name both the new admin and the creator.'
        );
    }

    #[Test]
    public function test_a_wrong_step_up_password_creates_nothing(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.requestAdminCreationKey'), [
                'password' => 'not-the-granters-password',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'miguel.admin@example.com']);
    }

    #[Test]
    public function test_the_consent_box_must_be_ticked(): void
    {
        $this->unlockAdminCreation();

        $this->actingAs($this->admin())
            ->post(route('admin.users.storeAdmin'), $this->validAdminPayload([
                'confirm' => null,
            ]))
            ->assertSessionHasErrors('confirm');

        $this->assertDatabaseMissing('users', ['email' => 'miguel.admin@example.com']);
    }

    #[Test]
    public function test_a_taken_email_is_refused(): void
    {
        User::create([
            'name' => 'Someone Else', 'email' => 'miguel.admin@example.com',
            'role' => 'store_owner', 'password' => 'password123',
        ]);

        $this->unlockAdminCreation();

        $this->actingAs($this->admin())
            ->post(route('admin.users.storeAdmin'), $this->validAdminPayload())
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', 'miguel.admin@example.com')->count());
    }

    #[Test]
    public function test_the_admin_creation_post_is_throttled_per_account(): void
    {
        $acting = $this->admin();

        // admin-grant allows 5 attempts/minute — every attempt counts,
        // even ones that fail validation (a web POST redirects back 302).
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($acting)
                ->post(route('admin.users.storeAdmin'), ['confirm' => null])
                ->assertRedirect();
        }

        $this->actingAs($acting)
            ->post(route('admin.users.storeAdmin'), ['confirm' => null])
            ->assertStatus(429);
    }

    #[Test]
    public function test_non_admins_cannot_use_the_administrator_form(): void
    {
        $farmer = User::create([
            'name' => 'Juan Dela Cruz', 'email' => 'farmer@example.com',
            'role' => 'farmer', 'password' => 'password123',
        ]);

        $this->actingAs($farmer)
            ->get(route('admin.users.create', ['type' => 'admin']))
            ->assertForbidden();

        $this->actingAs($farmer)
            ->post(route('admin.users.storeAdmin'), $this->validAdminPayload())
            ->assertForbidden();
    }

    // ── Activation (the invited admin has no password yet) ────────

    #[Test]
    public function test_the_invited_administrator_can_activate_and_reach_the_admin_dashboard(): void
    {
        $this->unlockAdminCreation();

        $this->actingAs($this->admin())
            ->post(route('admin.users.storeAdmin'), $this->validAdminPayload())
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'miguel.admin@example.com')->firstOrFail();

        // Activation routes are guest-only; the new administrator arrives
        // there from the emailed link, not from a signed-in session.
        auth()->logout();

        $code = Mail::sent(ActivationOtpMail::class)->last()->otp;

        $this->post(route('account.activation.verify'), [
            'email' => 'miguel.admin@example.com',
            'otp' => $code,
        ])->assertRedirect(route('account.activation.password'));

        $this->post(route('account.activation.complete'), [
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ])->assertRedirect(route('dashboard.redirect', ['role' => 'admin']));

        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertSame('admin', $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('secret12345', $user->password));
    }
}
