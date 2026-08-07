<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Quotation;
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
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Enter a valid email address.',
            'password.required' => 'Please enter your password.',
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
            'first_name.required' => 'Please enter your first name.',
            'first_name.regex' => 'First name may only contain letters, spaces, and hyphens.',
            'middle_initial.regex' => 'Middle initial may only contain letters, spaces, and periods.',
            'last_name.required' => 'Please enter your last name.',
            'last_name.regex' => 'Last name may only contain letters, spaces, and hyphens.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'This email is already registered. Please use a different email or log in.',
            'role.in' => 'Select a valid account role.',
            'password.required' => 'Please enter a password.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password_confirmation.required' => 'Please confirm your password.',
        ]);

        // Clean and normalize human inputs
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

        // Automatically log in the newly registered user
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

/**
     * Display the Admin Dashboard with live database metrics and user logs.
     */
    public function showAdminDashboard()
    {
        // 1. Calculate live totals directly from the database table
        $totalUsers = User::count();
        $storeOwnersCount = User::where('role', 'store_owner')->count();
        $farmersCount = User::where('role', 'farmer')->count();

        // 2. Fetch the latest 10 profile registrations
        $recentUsers = User::latest()->take(10)->get();

        // ─── Admin Dashboard Metrics ──────────────────────────────
        // Platform catalogue & activity
        $totalProducts = Product::count();
        $activeProducts = Product::where('status', 'active')->count();
        $totalQuotations = Quotation::count();
        $pendingQuotations = Quotation::where('status', Quotation::STATUS_PENDING)->count();

        // Recent platform-wide quotation activity
        $recentPlatformQuotations = Quotation::with(['farmer', 'storeOwner'])
            ->latest()
            ->take(5)
            ->get();

        // 3. Render the administration dashboard layout view safely with variables
        return view('dashboard.admin', compact(
            'totalUsers',
            'storeOwnersCount',
            'farmersCount',
            'recentUsers',
            'totalProducts',
            'activeProducts',
            'totalQuotations',
            'pendingQuotations',
            'recentPlatformQuotations'
        ));
    }
}

