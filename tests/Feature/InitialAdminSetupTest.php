<?php

namespace Tests\Feature;

use App\Console\Commands\CreateAdmin;
use App\Exceptions\Onboarding\InvalidOtpException;
use App\Mail\AdminEmailVerificationMail;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\AdminEmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

class InitialAdminSetupTest extends TestCase
{
    use RefreshDatabase;

    private function prepareCommand(): CreateAdmin
    {
        $command = app()->make(CreateAdmin::class);
        $command->setLaravel(app());

        return $command;
    }

    public static function newPendingAdmin(): User
    {
        return User::create([
            'name' => 'Pending Admin',
            'email' => 'pending.admin@sorsu.edu.ph',
            'role' => 'admin',
            'status' => User::STATUS_PENDING,
            'password' => Hash::make('Secret@123'),
            'email_verified_at' => null,
        ]);
    }

    // ── The interactive command ───────────────────────────────────

    public function test_command_creates_pending_admin_sends_otp_and_logs_activity(): void
    {
        Mail::fake();

        $tester = new CommandTester($this->prepareCommand());
        $tester->setInputs(['Aquilala', 'G.', 'Admin', 'admin.test@sorsu.edu.ph', 'Secret@123', 'Secret@123', 'yes']);
        $status = $tester->execute([]);

        $this->assertSame(CreateAdmin::SUCCESS, $status, "\n".$tester->getDisplay());
        $this->assertStringContainsString('6-digit verification code', $tester->getDisplay());

        $admin = User::where('email', 'admin.test@sorsu.edu.ph')->first();

        $this->assertNotNull($admin);
        $this->assertSame('Aquilala G. Admin', $admin->name);
        $this->assertSame('Aquilala', $admin->first_name);
        $this->assertSame('G.', $admin->middle_initial);
        $this->assertSame('Admin', $admin->last_name);
        $this->assertSame('admin', $admin->role);
        $this->assertSame(User::STATUS_PENDING, $admin->status);
        $this->assertNull($admin->email_verified_at);
        $this->assertNotSame('Secret@123', $admin->password);
        $this->assertTrue(Hash::check('Secret@123', $admin->password));

        $otpRow = $admin->emailOtps()->first();
        $this->assertNotNull($otpRow);
        $this->assertNotSame($otpRow->otp_hash, '');
        $this->assertFalse(Hash::check('not-the-code', $otpRow->otp_hash));

        Mail::assertSent(AdminEmailVerificationMail::class, function ($mail) use ($admin, $otpRow) {
            return $mail->hasTo($admin->email)
                && $mail->otp !== $otpRow->otp_hash
                && preg_match('/^\d{6}$/', $mail->otp) === 1
                && str_contains($mail->envelope()->subject, $mail->otp);
        });

        // The rendered body must actually build (shared OTP block component)
        // and open straight on the verification page.
        $verificationMail = Mail::sent(AdminEmailVerificationMail::class)->last();
        $verificationHtml = $verificationMail->render();
        $this->assertStringContainsString('/verify-email', $verificationHtml);
        $this->assertStringContainsString($verificationMail->otp, $verificationHtml);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'type' => ActivityLog::TYPE_ADMIN_CREATED,
        ]);
    }

    public function test_command_refuses_an_extra_admin(): void
    {
        User::create([
            'name' => 'Existing Admin',
            'email' => 'existing@aquilala.org',
            'role' => 'admin',
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('Secret@123'),
        ]);

        $tester = new CommandTester($this->prepareCommand());
        $tester->setInputs(['A', 'B', 'Reject', 'reject@aquilala.org', 'Secret@123', 'Secret@123', 'yes']);
        $status = $tester->execute([]);

        $this->assertSame(CreateAdmin::FAILURE, $status);
        $this->assertStringContainsString('already exists', $tester->getDisplay());
        $this->assertDatabaseMissing('users', ['email' => 'reject@aquilala.org']);
    }

    public function test_command_aborts_cleanly_on_taken_email(): void
    {
        User::create([
            'name' => 'Farmer A',
            'email' => 'taken@aquilala.org',
            'role' => 'farmer',
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('Secret@123'),
        ]);

        $tester = new CommandTester($this->prepareCommand());
        $tester->setInputs(['A', '', 'Admin', 'taken@aquilala.org', 'Secret@123', 'Secret@123', 'yes']);
        $status = $tester->execute([]);

        $this->assertSame(CreateAdmin::FAILURE, $status);
        $this->assertStringContainsString('already belongs to another account', $tester->getDisplay());
        $this->assertSame(1, User::where('email', 'taken@aquilala.org')->count());
    }

    // ── Sign-in guard while pending ───────────────────────────────

    public function test_pending_admin_cannot_sign_in_until_email_verified(): void
    {
        self::newPendingAdmin();

        $this->from(route('login'))
            ->post(route('login.post'), ['email' => 'pending.admin@sorsu.edu.ph', 'password' => 'Secret@123'])
            ->assertRedirect(route('email.verify.show'))
            ->assertSessionHas('info');

        $this->assertGuest();
    }

    // ── Email verification page ───────────────────────────────────

    public function test_verify_page_renders_for_guests(): void
    {
        $this->get(route('email.verify.show'))
            ->assertOk()
            ->assertSee('Verify your email')
            ->assertSee('Resend Code');
    }

    public function test_verify_expires_the_previous_code_when_resending(): void
    {
        Mail::fake();
        $admin = self::newPendingAdmin();
        $firstCode = AdminEmailVerificationService::issueOtp($admin);

        AdminEmailVerificationService::issueAndSendOtp($admin);

        $fresh = $admin->emailOtps()->latest('id')->first();
        $this->assertNotSame($firstCode, $fresh->otp_hash);

        $previous = $admin->emailOtps()->where('id', '<>', $fresh->id)->latest('id')->first();
        $this->assertNotNull($previous);
        $this->assertNotNull($previous->used_at);
    }

    public function test_verify_activates_account_with_correct_otp(): void
    {
        Mail::fake();
        $admin = self::newPendingAdmin();
        $code = AdminEmailVerificationService::issueOtp($admin);

        $this->post(route('email.verify'), [
            'email' => $admin->email,
            'otp' => $code,
        ])->assertRedirect(route('login'))->assertSessionHas('success');

        $this->assertSame(User::STATUS_ACTIVE, $admin->refresh()->status);
        $this->assertNotNull($admin->refresh()->email_verified_at);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'type' => ActivityLog::TYPE_EMAIL_VERIFIED,
        ]);
    }

    public function test_verify_rejects_wrong_otp_and_counts_attempts(): void
    {
        $admin = self::newPendingAdmin();
        AdminEmailVerificationService::issueOtp($admin);

        $this->post(route('email.verify'), ['email' => $admin->email, 'otp' => '000000'])
            ->assertSessionHasErrors('otp');

        $otpRow = $admin->emailOtps()->first();
        $this->assertSame(1, $otpRow->attempts);
        $this->assertSame(User::STATUS_PENDING, $admin->refresh()->status);

        foreach ([2, 3, 4, 5] as $attempt) {
            $this->post(route('email.verify'), ['email' => $admin->email, 'otp' => '000000'])
                ->assertSessionHasErrors('otp');
        }

        $this->assertSame(5, $otpRow->refresh()->attempts);
        $this->assertSame(User::STATUS_PENDING, $admin->refresh()->status);

        $this->post(route('email.verify'), ['email' => $admin->email, 'otp' => '111111'])
            ->assertSessionHasErrors('otp')
            ->assertSessionHasErrors('otp');

        $this->assertSame(User::STATUS_PENDING, $admin->refresh()->status);
    }

    public function test_verify_rejects_expired_otp(): void
    {
        $admin = self::newPendingAdmin();
        $code = AdminEmailVerificationService::issueOtp($admin);
        $admin->emailOtps()->first()->update(['expires_at' => now()->subMinute()]);

        $this->post(route('email.verify'), ['email' => $admin->email, 'otp' => $code])
            ->assertSessionHasErrors('otp');

        $this->assertSame(User::STATUS_PENDING, $admin->refresh()->status);
    }

    public function test_service_rejects_a_reused_code(): void
    {
        $admin = self::newPendingAdmin();
        $code = AdminEmailVerificationService::issueOtp($admin);

        AdminEmailVerificationService::verify($admin, $code);
        $this->assertSame(User::STATUS_ACTIVE, $admin->refresh()->status);

        $this->expectException(InvalidOtpException::class);
        AdminEmailVerificationService::verify($admin, $code);
    }

    public function test_resend_honours_the_cooldown_and_replaces_the_code(): void
    {
        Mail::fake();
        $admin = self::newPendingAdmin();
        $firstCode = AdminEmailVerificationService::issueOtp($admin);

        $this->post(route('email.verify.resend'), ['email' => $admin->email])
            ->assertSessionHasErrors('otp');

        $this->travel(61)->seconds();

        $this->post(route('email.verify.resend'), ['email' => $admin->email])
            ->assertSessionHas('success');

        $newCodeMail = Mail::sent(AdminEmailVerificationMail::class)->last()->otp;

        $this->post(route('email.verify'), ['email' => $admin->email, 'otp' => $firstCode])
            ->assertSessionHasErrors('otp');

        $this->post(route('email.verify'), ['email' => $admin->email, 'otp' => $newCodeMail])
            ->assertRedirect(route('login'));

        $this->assertSame(User::STATUS_ACTIVE, $admin->refresh()->status);
    }

    public function test_only_a_pending_admin_can_verify(): void
    {
        $farmer = User::create([
            'name' => 'Farmer A',
            'email' => 'farmer.a@sorsu.edu.ph',
            'role' => 'farmer',
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('Secret@123'),
        ]);

        $this->post(route('email.verify'), ['email' => $farmer->email, 'otp' => '123456'])
            ->assertSessionHasErrors('email');
    }
}
