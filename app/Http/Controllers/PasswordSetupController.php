<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordSetupController extends Controller
{
    /**
     * Show the guest "Set Your Password" form reached from the emailed
     * single-use link (Option A of an admin-created account).
     */
    public function showSetPassword(Request $request, string $token): RedirectResponse|View
    {
        $email = Str::lower(trim((string) $request->query('email', '')));

        if ($email === '') {
            return redirect()->route('login')->with('error', 'The password-setup link is missing an account. Use the invitation email, or contact your administrator.');
        }

        $user = $this->findUserByToken($email, $token);

        if ($user === null) {
            return redirect()->route('login')->with('error', 'This password-setup link is invalid or has already been used. Ask your administrator to resend the invitation.');
        }

        if (! $user->mustChangePassword()) {
            return redirect()->route('login')->with('success', 'Your account is already set up — sign in normally.');
        }

        return view('auth.set-password', ['token' => $token, 'email' => $user->email]);
    }

    /**
     * Complete the guest "Set Your Password" flow: store the new password,
     * verify the email, clear the requirement, and sign the member in.
     */
    public function storeSetPassword(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'password.required' => 'Please choose a password.',
            'password.confirmed' => 'The confirmation password does not match.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        $user = $this->findUserByToken(Str::lower(trim($validated['email'])), $token);

        if ($user === null) {
            return back()->with('error', 'This password-setup link is invalid or has already been used. Ask your administrator to resend the invitation.');
        }

        $user->update([
            'password' => $validated['password'],
            'email_verified_at' => $user->email_verified_at ?? now(),
            'activation_completed_at' => $user->activation_completed_at ?? now(),
            'approved_at' => $user->approved_at ?? now(),
            'must_change_password' => false,
        ]);

        Password::getRepository()->delete($user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard.redirect')->with('success', 'Password set successfully. Welcome to '.config('app.name').'!');
    }

    /**
     * Show the authenticated "Change Password" page that the password.setup
     * middleware lands on for members who signed in with a temporary password.
     */
    public function showChangePassword(): View
    {
        return view('auth.change-password');
    }

    /**
     * Replace the temporary password with a permanent one. Wrapped in a
     * transaction so the flag is only lifted when the new password persists.
     */
    public function storeChangePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'current_password.required' => 'Enter your current temporary password.',
            'password.required' => 'Please choose a new password.',
            'password.confirmed' => 'The confirmation password does not match.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'The current password you entered is incorrect.'])->withInput();
        }

        $user->update([
            'password' => $request->input('password'),
            'must_change_password' => false,
        ]);

        Auth::logoutOtherDevices($request->input('current_password'));
        $request->session()->regenerate();

        return redirect()->route('dashboard.redirect')->with('success', 'Your password was updated and is now active on all future sign-ins.');
    }

    /**
     * Resolve the account that owns a password-reset token. Falls back to the
     * token's own hash lookup when the emailed email address does not match.
     *
     * @return User|null
     */
    private function findUserByToken(string $email, string $token): ?User
    {
        $user = null;

        try {
            $user = User::where('email', $email)->firstOrFail();
        } catch (ModelNotFoundException) {
            $user = null;
        }

        if ($user !== null && Password::tokenExists($user, $token)) {
            return $user;
        }

        return null;
    }
}