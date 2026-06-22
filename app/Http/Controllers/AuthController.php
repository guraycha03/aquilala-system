<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showSignup()
    {
        return view('auth.signup');
    }

    public function login(Request $request)
    {
        // Real-world secure: use dynamic validation toggles based on environment
        $emailRule = app()->environment('production') ? 'email:rfc,dns,spoof' : 'email:rfc';
        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'max:254',
                $emailRule,
            ],
            'password' => ['required', 'string'],
        ], [
            'email.email' => 'Enter a valid email address.',
        ]);

        if (!Auth::attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
        ], $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Invalid email or password.',
            ])->withInput($request->except('password'));
        }

        $user = Auth::user();

        return redirect()->route('dashboard.redirect', ['role' => $user->role]);
    }

    public function signup(Request $request)
    {
        // Production gets strict DNS and Spoof checking. 
        // Local dev uses standard rfc format checking to avoid missing system extensions.
        $emailRule = app()->environment('production') ? 'email:rfc,dns,spoof' : 'email:rfc';

        $validated = $request->validate([
            'first_name'     => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
            'middle_initial' => ['nullable', 'string', 'max:2', 'regex:/^[\pL\s\.]+$/u'],
            'last_name'      => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
            'email' => [
                'required',
                'string',
                'max:254',
                $emailRule,
                'unique:users,email',
            ],
            'role' => ['required', Rule::in(['admin', 'store_owner', 'farmer'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
        ], [
            'role.in' => 'Select a valid account role.',
            'first_name.regex' => 'The first name field format is invalid.',
            'last_name.regex' => 'The last name field format is invalid.',
        ]);

        // Clean and normalize human inputs (Fixed lowercase to strtolower)
        $firstName = ucwords(strtolower(trim(preg_replace('/\s+/', ' ', $validated['first_name']))));
        $lastName  = ucwords(strtolower(trim(preg_replace('/\s+/', ' ', $validated['last_name']))));
        
        $mi = '';
        if (!empty($validated['middle_initial'])) {
            $cleanMi = strtoupper(trim(str_replace('.', '', $validated['middle_initial'])));
            $mi = $cleanMi . '.';
        }

        $fullName = trim(sprintf('%s %s %s', $firstName, $mi, $lastName));
        $fullName = preg_replace('/\s+/', ' ', $fullName); 

        $user = User::create([
            'name'           => $fullName,
            'first_name'     => $firstName,
            'middle_initial' => empty($mi) ? null : $mi,
            'last_name'      => $lastName,
            'email'          => strtolower(trim($validated['email'])),
            'role'           => $validated['role'],
            'password'       => Hash::make($validated['password']),
        ]);

        // Automatically log in the new farmer upon registration
        Auth::login($user);

        return redirect()->route('dashboard.redirect', ['role' => $user->role]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}