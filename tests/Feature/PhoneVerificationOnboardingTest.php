<?php

namespace Tests\Feature;

use App\Mail\AccountActivationMail;
use App\Models\User;
use App\Models\UserActivation;
use App\Services\UserInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class PhoneVerificationOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::create([
            'name'     => 'System Admin',
            'email'    => 'admin@aquilala.test',
            'role'     => 'admin',
            'status'   => User::STATUS_ACTIVE,
            'password' => 'password123',
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Juan',
            'last_name'  => 'Dela Cruz',
            'email'      => 'juan@farmer.test',
            'phone'      => '09171234567',
            'role'       => 'farmer',
        ], $overrides);
    }

    /**
     * Recover the plaintext activation token from the most recent
     * faked AccountActivationMail.
     */
    private function latestPlainToken(): string
    {
        $mailables = Mail::sent(AccountActivationMail::class);

        $this->assertGreaterThan(0, $mailables->count());

        return $mailables->last()->token;
    }

    // ── 1. Admin Registration & Uniqueness Check ──────────────────

    #[Test]
    public function admin_can_invite_user_which_creates_pending_account_and_sends_email(): void
    {
        Mail::fake();
        $this->actingAs($this->adminUser());

        $response = $this->postJson(route('admin.users.store'), $this->validPayload());

        $response->assertCreated();

        $user = User::where('email', 'juan@farmer.test')->firstOrFail();

        $this->assertSame(User::STATUS_PENDING_VERIFICATION, $user->status);
        $this->assertSame('+639171234567', $user->phone);
        $this->assertNull($user->phone_verified_at);
        $this->assertStringStartsWith('$2y$', $user->password, 'placeholder password must be a real bcrypt hash');
        $this->assertDatabaseCount('user_activation_tokens', 1);

        Mail::assertSent(AccountActivationMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email)
                && $mail->phoneNumber === '+639171234567'
                && str_contains($mail->activationUrl, '/confirm-account?token=');
        });
    }

    #[Test]
    public function duplicate_phone_number_is_rejected_with_exact_error(): void
    {
        Mail::fake();
        $this->actingAs($this->adminUser());

        UserInvitationService::invite($this->validPayload());

        $response = $this->postJson(route('admin.users.store'), $this->validPayload([
            'email' => 'other@farmer.test',
            'phone' => '+63 917-123-4567', // same number, different formatting
        ]));

        $response->assertUnprocessable()->assertJsonValidationErrors(['phone']);

        $this->assertEquals(
            'This phone number is already registered in the system.',
            $response->json('errors.phone.0')
        );
        Mail::assertSentCount(1);
    }

    #[Test]
    public function duplicate_email_is_rejected(): void
    {
        Mail::fake();
        $this->actingAs($this->adminUser());

        UserInvitationService::invite($this->validPayload());

        $response = $this->postJson(route('admin.users.store'), $this->validPayload([
            'email' => 'JUAN@farmer.test',
            'phone' => '09175550000', // different phone; only the email collides
        ]));

        $response->assertUnprocessable()->assertJsonValidationErrors(['email']);
        Mail::assertSentCount(1);
    }

    #[Test]
    public function invalid_phone_format_is_rejected(): void
    {
        Mail::fake();
        $this->actingAs($this->adminUser());

        $response = $this->postJson(route('admin.users.store'), $this->validPayload([
            'phone' => '0917123',
        ]));

        $response->assertUnprocessable()->assertJsonValidationErrors(['phone']);
        $this->assertDatabaseCount('users', 1); // only the admin exists
    }

    #[Test]
    public function mail_failure_keeps_pending_account_so_admin_can_resend(): void
    {
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andThrow(new RuntimeException('SMTP connection refused'));

        $this->actingAs($this->adminUser());

        $response = $this->postJson(route('admin.users.store'), $this->validPayload());

        $response->assertStatus(503);

        $this->assertDatabaseHas('users', [
            'email'  => 'juan@farmer.test',
            'status' => User::STATUS_PENDING_VERIFICATION,
        ]);
        $this->assertDatabaseCount('user_activation_tokens', 1);
    }

    // ── 2 & 3. Activation Email Link + Confirmation Page ──────────

    #[Test]
    public function activation_page_shows_form_with_registered_phone_for_valid_token(): void
    {
        Mail::fake();
        UserInvitationService::invite($this->validPayload());
        $plain = $this->latestPlainToken();

        $response = $this->get(route('account.activation.show', ['token' => $plain]));

        $response->assertOk()
            ->assertSee('Confirm your account')
            ->assertSee('+639171234567');
    }

    #[Test]
    public function user_can_activate_set_password_and_is_logged_into_dashboard(): void
    {
        Mail::fake();
        $user = UserInvitationService::invite($this->validPayload());
        $plain = $this->latestPlainToken();

        $response = $this->post(route('account.activation.complete'), [
            'token'                 => $plain,
            'password'              => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);

        $response->assertRedirect();
        $this->assertTrue(auth()->check());
        $this->assertEquals($user->id, auth()->id());

        $user->refresh();
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertTrue(Hash::check('secret12345', $user->password));
        $this->assertTrue($user->activations()->first()->isConsumed());
    }

    #[Test]
    public function expired_token_shows_expired_state_page(): void
    {
        Mail::fake();
        $user = UserInvitationService::invite($this->validPayload());
        $user->activations()->first()->update(['expires_at' => now()->subMinute()]);
        $plain = $this->latestPlainToken();

        $response = $this->get(route('account.activation.show', ['token' => $plain]));

        $response->assertOk()->assertSee('Link Expired');

        $complete = $this->post(route('account.activation.complete'), [
            'token'                 => $plain,
            'password'              => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);
        $complete->assertRedirect();
        $complete->assertSessionHasErrors('token');
    }

    #[Test]
    public function consumed_token_cannot_be_reused(): void
    {
        Mail::fake();
        $user = UserInvitationService::invite($this->validPayload());
        $plain = $this->latestPlainToken();

        $this->post(route('account.activation.complete'), [
            'token'                 => $plain,
            'password'              => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);

        $this->post(route('account.activation.complete'), [
            'token'                 => $plain,
            'password'              => 'anotherpass99',
            'password_confirmation' => 'anotherpass99',
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('secret12345', $user->password));
        $this->assertFalse(Hash::check('anotherpass99', $user->password));

        // Consumed token + active user => friendliest state is "already active".
        $page = $this->get(route('account.activation.show', ['token' => $plain]));
        $page->assertOk()->assertSee('Account Already Active');
    }

    #[Test]
    public function missing_or_garbage_token_renders_invalid_state(): void
    {
        $page = $this->get('/confirm-account?token=not-a-real-token');
        $page->assertOk()->assertSee('Invalid Activation Link');

        $empty = $this->get('/confirm-account');
        $empty->assertOk()->assertSee('Invalid Activation Link');
    }

    #[Test]
    public function pending_user_cannot_log_in_until_activation_completes(): void
    {
        Mail::fake();
        $user = UserInvitationService::invite($this->validPayload());
        // Simulate partial setup: password known while account still pending.
        $user->forceFill(['password' => 'knownpass123'])->save();

        $response = $this->post(route('login.post'), [
            'email'    => 'juan@farmer.test',
            'password' => 'knownpass123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertFalse(auth()->check());
    }

    #[Test]
    public function register_user_page_renders_with_breadcrumb_and_phone_prefix(): void
    {
        $this->actingAs($this->adminUser());

        $response = $this->get(route('admin.users.create'));

        $response->assertOk()
            ->assertSee('Register User')
            ->assertSee(route('admin.users.index'))
            ->assertSee('+639', false)
            ->assertSee('phoneLocal', false);
    }

    #[Test]
    public function developer_seeded_accounts_without_phone_log_in_freely(): void
    {
        // Admin accounts created via seeder/deployment have no invitation flow.
        $admin = User::create([
            'name'     => 'Platform Admin',
            'email'    => 'dev.admin@aquilala.test',
            'role'     => 'admin',
            'status'   => User::STATUS_ACTIVE,
            'password' => 'password123',
        ]);

        $response = $this->post(route('login.post'), [
            'email'    => 'dev.admin@aquilala.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertTrue(auth()->check());
        $this->assertEquals($admin->id, auth()->id());
    }

    #[Test]
    public function resending_invitation_voids_previous_token_and_resends_email(): void
    {
        Mail::fake();
        $this->actingAs($this->adminUser());

        $user = UserInvitationService::invite($this->validPayload());
        $oldPlain = $this->latestPlainToken();

        $response = $this->postJson(route('admin.users.resend', $user));

        $response->assertOk();

        // New plaintext token issued; old hash voided.
        $newPlain = $this->latestPlainToken();
        $this->assertNotSame($oldPlain, $newPlain);

        $tokens = $user->activations()->orderBy('id')->get();
        $this->assertCount(2, $tokens);
        $this->assertTrue($tokens[0]->fresh()->isConsumed(), 'old token must be voided');
        $this->assertFalse($tokens[1]->isConsumed());
        $this->assertNotSame($tokens[0]->token_hash, $tokens[1]->token_hash);

        // Drop the admin session: /confirm-account is guest-only.
        $this->app['auth']->forgetGuards();

        // Old link is now dead, the new one still shows the activation form.
        $this->get(route('account.activation.show', ['token' => $oldPlain]))
            ->assertOk()
            ->assertSee('Invalid Activation Link');
        $this->get(route('account.activation.show', ['token' => $newPlain]))
            ->assertOk()
            ->assertSee('Confirm your account');

        Mail::assertSentTimes(AccountActivationMail::class, 2);
    }

    #[Test]
    public function already_activated_user_shows_already_active_state(): void
    {
        Mail::fake();
        $user = UserInvitationService::invite($this->validPayload());
        $plain = $this->latestPlainToken();

        $this->post(route('account.activation.complete'), [
            'token'                 => $plain,
            'password'              => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);

        auth()->logout();

        $page = $this->get(route('account.activation.show', ['token' => $plain]));
        $page->assertOk()->assertSee('Account Already Active');
    }
}
