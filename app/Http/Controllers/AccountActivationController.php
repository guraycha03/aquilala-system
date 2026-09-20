<?php

namespace App\Http\Controllers;

use App\Exceptions\Onboarding\ExpiredOtpException;
use App\Exceptions\Onboarding\InvalidOtpException;
use App\Exceptions\Onboarding\MailDeliveryException;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\EmailOtpService;
use App\Services\UserInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AccountActivationController extends Controller
{
    /**
     * Landing page for account activation. Identity is resolved from an
     * emailed entry-token link or from a pending-sign-in redirect; otherwise
     * the user is asked for their email so a code can be (re)sent.
     */
    public function index(Request $request)
    {
        $email = null;
        $user = null;

        $state = UserInvitationService::resolveEntryToken($request->query('token'));

        if ($state['state'] === 'form' && isset($state['user'])) {
            $user = $state['user'];
            $email = $user->email;
            $request->session()->put('activation_email', $email);
        } elseif ($state['state'] === 'already_active' && isset($state['user'])) {
            return redirect()->route('login')
                ->with('info', 'That account is already active. You can sign in.');
        } elseif (($candidate = $request->session()->get('activation_email'))) {
            $email = $candidate;
            $user = $this->findPendingAccount($candidate);
        }

        $cooldownSeconds = 0;
        $expiresSeconds = 0;

        if ($user) {
            $nextAt = EmailOtpService::nextResendAt($user);
            $cooldownSeconds = max(0, (int) $nextAt->diffInSeconds(now(), true));

            $expiresAt = EmailOtpService::expiresAt($user, EmailOtp::PURPOSE_ACTIVATION);
            $expiresSeconds = $expiresAt ? max(0, (int) $expiresAt->diffInSeconds(now(), true)) : 0;
        }

        return view('auth.activate-otp', [
            'state' => $state['state'],
            'email' => $email,
            'user' => $user,
            'tokenExpired' => $state['state'] === 'expired',
            'cooldownSeconds' => $cooldownSeconds,
            'expiresSeconds' => $expiresSeconds,
        ]);
    }

    /**
     * Verify the emailed 6-digit code for the pending account. On success the
     * user is walked to the choose-your-password step.
     */
    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'max:254', 'email:rfc'],
            'otp' => ['required', 'string', 'regex:/^\d{6}$/'],
        ], [
            'email.required' => 'Please enter the email address on your account.',
            'email.email' => 'Enter a valid email address.',
            'otp.required' => 'Please enter the 6-digit verification code.',
            'otp.regex' => 'The verification code must be exactly 6 digits.',
        ]);

        $user = $this->findPendingAccount(strtolower(trim($validated['email'])));

        if (! $user) {
            return $this->verificationFailure($request, 'The account details you entered do not match a pending account.');
        }

        try {
            EmailOtpService::verifyAndConsume($user, $validated['otp'], EmailOtp::PURPOSE_ACTIVATION);
        } catch (InvalidOtpException|ExpiredOtpException $e) {
            return $this->verificationFailure($request, $e->getMessage());
        }

        $request->session()->put('activation_user_id', (int) $user->id);
        $request->session()->put('activation_email', (string) $user->email);

        return redirect()->route('account.activation.password');
    }

    /**
     * Re-deliver an activation code. Responds identically whether or not a
     * pending account exists for the address, so the page never reveals which
     * emails belong to pending accounts.
     */
    public function resend(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'max:254', 'email:rfc'],
        ], [
            'email.required' => 'Please enter the email address on your account.',
            'email.email' => 'Enter a valid email address.',
        ]);

        $email = strtolower(trim($validated['email']));
        $user = $this->findPendingAccount($email);

        if (! $user) {
            $request->session()->put('activation_email', $email);

            return back()->with('success', 'If an account is awaiting activation for that email, a new verification code is on its way.');
        }

        $nextAt = EmailOtpService::nextResendAt($user);

        if ($nextAt->isFuture()) {
            $seconds = max(1, (int) $nextAt->diffInSeconds(now(), true));

            return back()
                ->withErrors(['otp' => "Please wait {$seconds} second".($seconds === 1 ? '' : 's').' before requesting another code.'])
                ->withInput();
        }

        $request->session()->put('activation_email', $email);

        try {
            EmailOtpService::issueAndSend($user, EmailOtp::PURPOSE_ACTIVATION);
        } catch (MailDeliveryException $e) {
            return back()->withErrors(['otp' => $e->getMessage()]);
        }

        return back()->with('success', 'A new verification code was sent. The previous code is no longer valid.');
    }

    /**
     * Choose-your-password step. Only reachable after a successful OTP
     * verification in this browser session.
     */
    public function password(Request $request)
    {
        $userId = $request->session()->get('activation_user_id');

        if (! $userId) {
            return redirect()->route('account.activation.index')
                ->withErrors(['otp' => 'Verify your email with a valid code before choosing a password.']);
        }

        $user = User::whereKey($userId)->where('status', User::STATUS_PENDING)->first();

        if (! $user) {
            $request->session()->forget(['activation_user_id', 'activation_email']);

            return redirect()->route('login')
                ->with('info', 'This activation link is no longer valid. Please sign in to continue.');
        }

        return view('auth.activate-password', [
            'maskedEmail' => EmailOtpService::maskEmail($user->email),
        ]);
    }

    /**
     * Finalize activation: set the password, flip the account to active, log
     * the user in, and route them to their role dashboard.
     */
    public function complete(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('activation_user_id');
        $user = $userId ? User::whereKey($userId)->where('status', User::STATUS_PENDING)->first() : null;

        if (! $user) {
            return redirect()->route('account.activation.index')
                ->withErrors(['otp' => 'This activation session is no longer valid. Please start again.']);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ], [
            'password.required' => 'Please choose a password.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.max' => 'Password must not exceed 255 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => $user->email_verified_at ?? now(),
                'activation_completed_at' => now(),
            ])->save();

            EmailOtpService::purge($user, EmailOtp::PURPOSE_ACTIVATION);
            UserInvitationService::consumeEntryTokens($user);
        });

        $request->session()->forget(['activation_user_id', 'activation_email']);

        ActivityLogger::log(
            'user.activated',
            "Activated account for {$user->name} via email verification",
            $user,
            ['email' => $user->email],
            $user
        );

        Auth::login($user);
        $request->session()->regenerate();

        Log::info('Account activated via email OTP.', [
            'user_id' => $user->id,
            'role' => $user->role,
        ]);

        ActivityLogger::userLoggedIn($user, 'activation');

        return redirect()->route('dashboard.redirect', ['role' => $user->role])
            ->with('success', 'Your account is now active. Welcome to Aquilala!');
    }

    private function findPendingAccount(string $email): ?User
    {
        return User::where('email', $email)
            ->whereIn('role', ['farmer', 'store_owner'])
            ->where('status', User::STATUS_PENDING)
            ->first();
    }

    private function verificationFailure(Request $request, string $message): RedirectResponse
    {
        return back()
            ->withErrors(['otp' => $message])
            ->withInput($request->except('otp'));
    }
}
