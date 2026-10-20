<?php

namespace Tests\Feature;

use App\Exceptions\Onboarding\InvalidOtpException;
use App\Mail\ActivationOtpMail;
use App\Mail\PasswordResetOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use App\Models\UserActivation;
use App\Services\EmailOtpService;
use App\Services\UserInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class AccountActivationFlowTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────

    private function adminUser(): User
    {
        return User::create([
            'name' => 'System Admin',
            'email' => 'admin@aquilala.test',
            'role' => 'admin',
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('password123'),
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@farmer.test',
            'phone' => '09171234567',
            'role' => 'farmer',
        ], $overrides);
    }

    private function pendingFarmer(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@farmer.test',
            'phone' => '+639171234567',
            'role' => 'farmer',
            'status' => User::STATUS_PENDING,
            'password' => Hash::make(Str::random(64)),
        ], $overrides));
    }

    private function issueEntryFor(User $user): string
    {
        $plain = bin2hex(random_bytes(32));

        $user->activations()->create([
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addHours(UserActivation::TOKEN_TTL_HOURS),
        ]);

        return $plain;
    }

    private function latestActivationCode(): string
    {
        return Mail::sent(ActivationOtpMail::class)->last()->otp;
    }

    // ── Admin-created invitation ──────────────────────────────────

    #[Test]
    public function admin_can_invite_user_which_creates_pending_account_and_sends_otp_email(): void
    {
        Mail::fake();
        $this->actingAs($this->adminUser());

        $response = $this->postJson(route('admin.users.store'), $this->validPayload());

        $response->assertCreated();

        $user = User::where('email', 'juan@farmer.test')->firstOrFail();

        $this->assertSame(User::STATUS_PENDING, $user->status);
        $this->assertSame('+639171234567', $user->phone);
        $this->assertStringStartsWith('$2y$', $user->password, 'placeholder password must be a real bcrypt hash');
        $this->assertDatabaseCount('user_activation_tokens', 1);

        Mail::assertSent(ActivationOtpMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email)
                && $mail->otp !== $user->emailOtps()->first()->otp_hash
                && preg_match('/^\d{6}$/', $mail->otp) === 1;
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
    public function mail_failure_still_creates_pending_account_without_failing_the_invitation(): void
    {
        Mail::shouldReceive('mailer')->with('resend')->andReturnSelf();
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andThrow(new RuntimeException('SMTP connection refused'));

        $this->actingAs($this->adminUser());

        $response = $this->postJson(route('admin.users.store'), $this->validPayload());

        // A transient mail outage must not fail the invitation: the account is
        // created, stays pending, and keeps its stored activation code so the
        // admin can resend it later. The response must still warn the admin.
        $response->assertCreated();
        $this->assertStringContainsString('could not be sent', $response->json('message'));

        $this->assertDatabaseHas('users', [
            'email' => 'juan@farmer.test',
            'status' => User::STATUS_PENDING,
        ]);
        $this->assertDatabaseCount('user_activation_tokens', 1);
    }

    #[Test]
    public function admin_can_create_user_without_a_phone_number(): void
    {
        Mail::fake();
        $this->actingAs($this->adminUser());

        $response = $this->postJson(route('admin.users.store'), $this->validPayload([
            'phone' => '',
        ]));

        $response->assertCreated();

        $user = User::where('email', 'juan@farmer.test')->firstOrFail();

        $this->assertNull($user->phone, 'blank phone must be stored as NULL');
        $this->assertSame(User::STATUS_PENDING, $user->status);
    }

    #[Test]
    public function resend_reports_delivery_failure_with_the_underlying_reason(): void
    {
        Mail::shouldReceive('mailer')->with('resend')->andReturnSelf();
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andThrow(new RuntimeException('SMTP connection refused'));

        $this->actingAs($this->adminUser());

        $user = $this->pendingFarmer();

        $response = $this->postJson(route('admin.users.resend', $user));

        $response->assertStatus(503);
        $this->assertStringContainsString('SMTP connection refused', $response->json('message'));
    }

    // ── Activation entry page (token states) ──────────────────────

    #[Test]
    public function activation_page_renders_form_for_valid_entry_token(): void
    {
        $user = $this->pendingFarmer();
        $plain = $this->issueEntryFor($user);

        $this->get(route('account.activation.index', ['token' => $plain]))
            ->assertOk()
            ->assertSee('Activate your account')
            ->assertSee(EmailOtpService::maskEmail($user->email));
    }

    #[Test]
    public function activation_page_renders_email_entry_mode_without_token(): void
    {
        $this->get(route('account.activation.index'))
            ->assertOk()
            ->assertSee('Activate your account');
    }

    #[Test]
    public function garbage_entry_token_shows_not_recognized_state(): void
    {
        $this->get(route('account.activation.index', ['token' => 'not-a-real-token']))
            ->assertOk()
            ->assertSee('was not recognized');
    }

    #[Test]
    public function expired_entry_token_shows_expired_state(): void
    {
        $user = $this->pendingFarmer();
        $plain = $this->issueEntryFor($user);
        $user->activations()->first()->update(['expires_at' => now()->subMinute()]);

        $this->get(route('account.activation.index', ['token' => $plain]))
            ->assertOk()
            ->assertSee('has expired');
    }

    // ── Full activation flow ──────────────────────────────────────

    #[Test]
    public function user_can_verify_otp_set_password_and_reach_farmer_dashboard(): void
    {
        Mail::fake();
        $user = UserInvitationService::invite($this->validPayload());
        $code = $this->latestActivationCode();

        $this->post(route('account.activation.verify'), [
            'email' => 'juan@farmer.test',
            'otp' => $code,
        ])->assertRedirect(route('account.activation.password'));

        $this->assertSame((int) $user->id, (int) session('activation_user_id'));

        $this->get(route('account.activation.password'))
            ->assertOk()
            ->assertSee('Great, your email is verified')
            ->assertSee(EmailOtpService::maskEmail($user->email));

        $this->post(route('account.activation.complete'), [
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ])->assertRedirect(route('dashboard.redirect', ['role' => 'farmer']));

        $this->assertTrue(auth()->check());
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->activation_completed_at);
        $this->assertTrue(Hash::check('secret12345', $user->password));

        // All activation OTPs and entry tokens are now invalid.
        $this->assertTrue($user->emailOtps()->whereNull('used_at')->doesntExist());
        $this->assertTrue($user->activations()->whereNull('consumed_at')->doesntExist());
    }

    #[Test]
    public function store_owner_completion_lands_on_store_dashboard(): void
    {
        Mail::fake();
        $user = UserInvitationService::invite($this->validPayload(['role' => 'store_owner']));
        $code = $this->latestActivationCode();

        $this->post(route('account.activation.verify'), [
            'email' => 'juan@farmer.test',
            'otp' => $code,
        ])->assertRedirect(route('account.activation.password'));

        $this->post(route('account.activation.complete'), [
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ])->assertRedirect(route('dashboard.redirect', ['role' => 'store_owner']));

        $this->assertSame(User::STATUS_ACTIVE, $user->refresh()->status);
    }

    #[Test]
    public function already_active_account_redirects_to_login_when_link_is_reused(): void
    {
        Mail::fake();
        $user = UserInvitationService::invite($this->validPayload());
        $code = $this->latestActivationCode();
        $entryPlain = bin2hex(random_bytes(32));
        $user->activations()->create([
            'token_hash' => hash('sha256', $entryPlain),
            'expires_at' => now()->addHour(),
        ]);

        $this->post(route('account.activation.verify'), ['email' => $user->email, 'otp' => $code]);
        $this->post(route('account.activation.complete'), [
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);

        // Another browser, signed out, opens the link that was already used.
        auth()->logout();

        $this->get(route('account.activation.index', ['token' => $entryPlain]))
            ->assertRedirect(route('login'))
            ->assertSessionHas('info');
    }

    #[Test]
    public function wrong_otp_is_rejected_and_attempts_are_counted(): void
    {
        $user = $this->pendingFarmer();
        EmailOtpService::issue($user, EmailOtp::PURPOSE_ACTIVATION);

        $this->post(route('account.activation.verify'), [
            'email' => $user->email,
            'otp' => '000000',
        ])->assertSessionHasErrors('otp');

        $otpRow = $user->emailOtps()->first();
        $this->assertSame(1, $otpRow->attempts);
        $this->assertSame(User::STATUS_PENDING, $user->refresh()->status);

        foreach ([2, 3, 4, 5] as $attempt) {
            $this->post(route('account.activation.verify'), [
                'email' => $user->email,
                'otp' => '000000',
            ])->assertSessionHasErrors('otp');
        }

        $this->assertSame(5, $otpRow->refresh()->attempts);
        $this->assertSame(User::STATUS_PENDING, $user->refresh()->status);

        // The code is now locked out even with a plausible value.
        $this->post(route('account.activation.verify'), [
            'email' => $user->email,
            'otp' => '111111',
        ])->assertSessionHasErrors('otp');

        $this->assertSame(User::STATUS_PENDING, $user->refresh()->status);
    }

    #[Test]
    public function expired_otp_is_rejected(): void
    {
        $user = $this->pendingFarmer();
        $code = EmailOtpService::issue($user, EmailOtp::PURPOSE_ACTIVATION);
        $user->emailOtps()->first()->update(['expires_at' => now()->subMinute()]);

        $this->post(route('account.activation.verify'), [
            'email' => $user->email,
            'otp' => $code,
        ])->assertSessionHasErrors('otp');

        $this->assertSame(User::STATUS_PENDING, $user->refresh()->status);
    }

    #[Test]
    public function otp_is_single_use_at_the_service_level(): void
    {
        $user = $this->pendingFarmer();
        $code = EmailOtpService::issue($user, EmailOtp::PURPOSE_ACTIVATION);

        EmailOtpService::verifyAndConsume($user, $code, EmailOtp::PURPOSE_ACTIVATION);

        $this->expectException(InvalidOtpException::class);
        EmailOtpService::verifyAndConsume($user, $code, EmailOtp::PURPOSE_ACTIVATION);
    }

    #[Test]
    public function resend_respects_cooldown_and_invalidates_the_previous_code(): void
    {
        Mail::fake();
        $user = $this->pendingFarmer();
        EmailOtpService::issueAndSend($user, EmailOtp::PURPOSE_ACTIVATION);
        $firstCode = $this->latestActivationCode();

        $this->post(route('account.activation.resend'), ['email' => $user->email])
            ->assertSessionHasErrors('otp');

        $this->travel(61)->seconds();

        $this->post(route('account.activation.resend'), ['email' => $user->email])
            ->assertSessionHas('success');

        $secondCode = $this->latestActivationCode();
        $this->assertNotSame($firstCode, $secondCode);

        $this->post(route('account.activation.verify'), ['email' => $user->email, 'otp' => $firstCode])
            ->assertSessionHasErrors('otp');

        $this->post(route('account.activation.verify'), ['email' => $user->email, 'otp' => $secondCode])
            ->assertRedirect(route('account.activation.password'));
    }

    #[Test]
    public function password_step_requires_a_prior_otp_verification(): void
    {
        $this->get(route('account.activation.password'))
            ->assertRedirect(route('account.activation.index'))
            ->assertSessionHasErrors('otp');
    }

    // ── Sign-in guards ────────────────────────────────────────────

    #[Test]
    public function pending_user_login_redirects_to_activation_page(): void
    {
        $this->pendingFarmer(['password' => Hash::make('knownpass123')]);

        $this->post(route('login.post'), [
            'email' => 'juan@farmer.test',
            'password' => 'knownpass123',
        ])->assertRedirect(route('account.activation.index'))
            ->assertSessionHas('info');

        $this->assertSame('juan@farmer.test', session('activation_email'));
        $this->assertGuest();
    }

    #[Test]
    public function disabled_account_cannot_sign_in(): void
    {
        $this->pendingFarmer([
            'status' => User::STATUS_DISABLED,
            'password' => Hash::make('knownpass123'),
        ]);

        $this->from(route('login'))
            ->post(route('login.post'), [
                'email' => 'juan@farmer.test',
                'password' => 'knownpass123',
            ])
            ->assertSessionHasErrors('email')
            ->assertSessionHas('errors');

        $this->assertGuest();
    }

    #[Test]
    public function active_user_signs_in_normally(): void
    {
        $this->pendingFarmer(['status' => User::STATUS_ACTIVE, 'password' => Hash::make('knownpass123')]);

        $this->post(route('login.post'), [
            'email' => 'juan@farmer.test',
            'password' => 'knownpass123',
        ])->assertRedirect();

        $this->assertTrue(auth()->check());
    }

    // ── Forgot password (OTP reset) ───────────────────────────────

    #[Test]
    public function user_can_reset_password_and_sign_in_with_the_new_one(): void
    {
        Mail::fake();
        $admin = User::create([
            'name' => 'Platform Admin',
            'email' => 'dev.admin@aquilala.test',
            'role' => 'admin',
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('Old@pass123'),
        ]);

        $this->post(route('password.email'), ['email' => $admin->email])
            ->assertRedirect(route('password.verify'))
            ->assertSessionHas('success');

        $code = Mail::sent(PasswordResetOtpMail::class)->last()->otp;
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        // The email must link to the code-entry page, not back to the
        // email-only forgot-password form.
        $html = Mail::sent(PasswordResetOtpMail::class)->last()->render();
        $this->assertStringContainsString('/forgot-password/verify', $html);
        $this->assertStringNotContainsString('/forgot-password"', $html);

        // Arriving at the code step, the email from the previous step is prefilled.
        $this->get(route('password.verify'))->assertOk()->assertSee('verification code')->assertSee($admin->email, false);

        $this->post(route('password.verify.post'), ['email' => $admin->email, 'otp' => $code])
            ->assertRedirect(route('password.reset.show'));

        $this->get(route('password.reset.show'))->assertOk()->assertSee('Choose a new password');

        $this->post(route('password.reset'), [
            'password' => 'New@pass456',
            'password_confirmation' => 'New@pass456',
        ])->assertRedirect(route('login'))->assertSessionHas('success');

        $this->assertTrue(Hash::check('New@pass456', $admin->refresh()->password));
        $this->assertDatabaseMissing('sessions', ['user_id' => $admin->id]);

        $this->post(route('login.post'), [
            'email' => $admin->email,
            'password' => 'New@pass456',
        ])->assertRedirect();

        $this->assertTrue(auth()->check());
    }

    #[Test]
    public function password_reset_is_generic_for_unknown_emails(): void
    {
        Mail::fake();

        $this->post(route('password.email'), ['email' => 'nobody@nowhere.test'])
            ->assertSessionHas('success');

        $this->post(route('password.verify.post'), [
            'email' => 'nobody@nowhere.test',
            'otp' => '123456',
        ])->assertSessionHasErrors('otp');

        Mail::assertNothingSent();
    }

    #[Test]
    public function password_reset_ignores_pending_and_disabled_accounts(): void
    {
        Mail::fake();
        $pending = $this->pendingFarmer();
        $disabled = $this->pendingFarmer([
            'email' => 'disabled@farmer.test',
            'phone' => '+6399888777666',
            'status' => User::STATUS_DISABLED,
        ]);

        // Same generic success message as a brand-new address; no code sent.
        $this->post(route('password.email'), ['email' => $pending->email])
            ->assertSessionHas('success');

        $this->post(route('password.email'), ['email' => $disabled->email])
            ->assertSessionHas('success');

        Mail::assertNothingSent();
    }

    #[Test]
    public function reset_verify_rejects_a_code_for_a_pending_account(): void
    {
        $pending = $this->pendingFarmer();

        $this->post(route('password.verify.post'), [
            'email' => $pending->email,
            'otp' => '123456',
        ])->assertSessionHasErrors('otp');
    }
}
