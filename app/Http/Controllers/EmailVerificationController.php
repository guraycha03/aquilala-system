<?php

namespace App\Http\Controllers;

use App\Exceptions\Onboarding\ExpiredOtpException;
use App\Exceptions\Onboarding\InvalidOtpException;
use App\Exceptions\Onboarding\MailDeliveryException;
use App\Models\User;
use App\Services\AdminEmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function show()
    {
        return view('auth.verify-email');
    }

    public function verify(Request $request): RedirectResponse
    {
        $rule = app()->environment('production') ? 'email:rfc,dns,spoof' : 'email:rfc';

        $validated = $request->validate([
            'email' => ['required', 'string', 'max:254', $rule],
            'otp' => ['required', 'string', 'regex:/^\d{6}$/'],
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Enter a valid email address.',
            'otp.required' => 'Please enter the 6-digit verification code.',
            'otp.regex' => 'The verification code must be exactly 6 digits.',
        ]);

        $user = $this->findPendingAdmin(strtolower(trim($validated['email'])));

        if (! $user) {
            return back()->withErrors(['email' => 'No account is awaiting verification for that email address.']);
        }

        try {
            AdminEmailVerificationService::verify($user, $validated['otp']);
        } catch (InvalidOtpException|ExpiredOtpException $e) {
            return back()
                ->withErrors(['otp' => $e->getMessage()])
                ->withInput($request->except('otp'));
        }

        return redirect()->route('login')
            ->with('success', 'Your email has been verified and your account is now active. You can sign in.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $rule = app()->environment('production') ? 'email:rfc,dns,spoof' : 'email:rfc';

        $validated = $request->validate([
            'email' => ['required', 'string', 'max:254', $rule],
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Enter a valid email address.',
        ]);

        $user = $this->findPendingAdmin(strtolower(trim($validated['email'])));

        if (! $user) {
            return back()->withErrors(['email' => 'No account is awaiting verification for that email address.']);
        }

        $nextAt = AdminEmailVerificationService::nextResendAt($user);

        if ($nextAt->isFuture()) {
            $seconds = max(1, (int) $nextAt->diffInSeconds(now(), true));

            return back()
                ->withErrors(['otp' => "Please wait {$seconds} second".($seconds === 1 ? '' : 's').' before requesting another code.'])
                ->withInput();
        }

        try {
            AdminEmailVerificationService::issueAndSendOtp($user);
        } catch (MailDeliveryException $e) {
            return back()->withErrors(['otp' => $e->getMessage()]);
        }

        return back()->with('success', 'A new verification code was sent to '.$user->email.'. The previous code is no longer valid.');
    }

    private function findPendingAdmin(string $email): ?User
    {
        return User::where('email', $email)
            ->where('role', 'admin')
            ->where('status', User::STATUS_PENDING)
            ->first();
    }
}
