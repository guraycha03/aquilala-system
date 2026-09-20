<?php

namespace App\Http\Controllers;

use App\Exceptions\Onboarding\ExpiredOtpException;
use App\Exceptions\Onboarding\InvalidOtpException;
use App\Exceptions\Onboarding\MailDeliveryException;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    public function showRequest()
    {
        return view('auth.forgot-password');
    }

    /**
     * Email the reset code. Responds identically for unknown addresses so the
     * endpoint cannot be used to enumerate which emails have accounts.
     */
    public function sendRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'max:254', 'email:rfc'],
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Enter a valid email address.',
        ]);

        $user = User::where('email', strtolower(trim($validated['email'])))
            ->where('status', User::STATUS_ACTIVE)
            ->first();

        if ($user) {
            $nextAt = EmailOtpService::nextResendAt($user);

            if ($nextAt->isFuture()) {
                $seconds = max(1, (int) $nextAt->diffInSeconds(now(), true));

                return back()
                    ->withErrors(['email' => "Please wait {$seconds} second".($seconds === 1 ? '' : 's').' before requesting another code.'])
                    ->withInput();
            }

            try {
                EmailOtpService::issueAndSend($user, EmailOtp::PURPOSE_PASSWORD_RESET);
            } catch (MailDeliveryException $e) {
                return back()->withErrors(['email' => $e->getMessage()]);
            }
        }

        return back()->with('success', 'If an account exists for that email address, a verification code will be sent shortly. Check your inbox.');
    }

    public function showVerify()
    {
        if (session()->has('reset_user_id')) {
            return redirect()->route('password.reset.show');
        }

        return view('auth.reset-otp');
    }

    /**
     * Validate the emailed reset code and open the change-password step.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'max:254', 'email:rfc'],
            'otp' => ['required', 'string', 'regex:/^\d{6}$/'],
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Enter a valid email address.',
            'otp.required' => 'Please enter the 6-digit verification code.',
            'otp.regex' => 'The verification code must be exactly 6 digits.',
        ]);

        $user = User::where('email', strtolower(trim($validated['email'])))
            ->where('status', User::STATUS_ACTIVE)
            ->first();

        if (! $user) {
            return back()
                ->withErrors(['otp' => 'We could not verify those details. Request a new code and try again.'])
                ->withInput($request->except('otp'));
        }

        try {
            EmailOtpService::verifyAndConsume($user, $validated['otp'], EmailOtp::PURPOSE_PASSWORD_RESET);
        } catch (InvalidOtpException|ExpiredOtpException $e) {
            return back()
                ->withErrors(['otp' => $e->getMessage()])
                ->withInput($request->except('otp'));
        }

        $request->session()->put('reset_user_id', (int) $user->id);

        return redirect()->route('password.reset.show');
    }

    public function showReset()
    {
        $userId = session()->get('reset_user_id');

        if (! $userId) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Verify your email with a valid code before resetting your password.']);
        }

        return view('auth.reset-password');
    }

    /**
     * Apply the new password, invalidate every existing session for the
     * account, and purge all outstanding reset codes.
     */
    public function reset(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('reset_user_id');
        $user = $userId ? User::whereKey($userId)->first() : null;

        if (! $user || $user->isDisabled() || $user->isPending()) {
            return redirect()->route('login');
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
            ])->save();

            EmailOtpService::purge($user, EmailOtp::PURPOSE_PASSWORD_RESET);
        });

        // Force every other session (including the current one) to sign out.
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $request->session()->forget(['reset_user_id', 'reset_email_hint']);
        $request->session()->regenerate();

        return redirect()->route('login')
            ->with('success', 'Your password has been reset. You can now sign in with your new password.');
    }
}
