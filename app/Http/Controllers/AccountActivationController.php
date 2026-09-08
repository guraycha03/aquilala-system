<?php

namespace App\Http\Controllers;

use App\Exceptions\Onboarding\ExpiredActivationTokenException;
use App\Exceptions\Onboarding\InvalidActivationTokenException;
use App\Services\UserInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AccountActivationController extends Controller
{
    /**
     * Landing page for the email activation link.
     * Renders the password form or the appropriate error state.
     */
    public function show(Request $request)
    {
        $token = (string) $request->query('token', '');
        $state = $token === '' ? ['state' => 'invalid'] : UserInvitationService::resolveToken($token);

        return view('auth.confirm-account', [
            'state' => $state['state'],
            'user'  => $state['user'] ?? null,
            'token' => $token,
        ]);
    }

    /**
     * Complete activation: set password, verify phone, log in, redirect to dashboard.
     */
    public function complete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token'    => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'password.required' => 'Please enter a password.',
            'password.min'      => 'Password must be at least 8 characters.',
        ]);

        try {
            $user = UserInvitationService::activate($validated['token'], $validated['password']);
        } catch (InvalidActivationTokenException | ExpiredActivationTokenException $e) {
            return redirect()
                ->route('account.activation.show')
                ->withErrors(['token' => $e->getMessage()]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        Log::info('Account activated via email link.', [
            'user_id' => $user->id,
            'phone'   => $user->phone,
        ]);

        \App\Services\ActivityLogger::userLoggedIn($user, 'activation link');

        return redirect()->route('dashboard.redirect', ['role' => $user->role])
            ->with('success', 'Your account is now active. Welcome to Aquilala!');
    }
}
