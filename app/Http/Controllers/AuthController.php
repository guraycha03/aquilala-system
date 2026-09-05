<?php

namespace App\Http\Controllers;

use App\Models\Category;
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

    /**
     * Confirmation page shown after a farmer submits a registration request.
     */
    public function showRegistrationSubmitted()
    {
        return view('auth.registration-submitted');
    }

    public function login(Request $request)
    {
        // Login method toggle: users may sign in with email OR phone number
        $method = $request->input('login_method') === 'phone' ? 'phone' : 'email';
        $errorKey = $method === 'phone' ? 'phone' : 'email';

        if ($method === 'phone') {
            $validated = $request->validate([
                'phone'    => ['required', 'string', 'max:25'],
                'password' => ['required', 'string'],
            ], [
                'phone.required'    => 'Please enter your mobile number.',
                'phone.max'         => 'Mobile number is too long.',
                'password.required' => 'Please enter your password.',
            ]);

            // Accepts +639XXXXXXXXX, 09XXXXXXXXX, 9XXXXXXXXX etc.
            $phone = \App\Services\UserInvitationService::normalizePhone($validated['phone']);

            if ($phone === null) {
                return back()->withErrors([
                    'phone' => 'Enter a valid Philippine mobile number — the 9 digits after +639.',
                ])->withInput($request->except('password'));
            }

            $credentials = [
                'phone'    => $phone,
                'password' => $validated['password'],
            ];
        } else {
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

            $credentials = [
                'email'    => $validated['email'],
                'password' => $validated['password'],
            ];
        }

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                $errorKey => $method === 'phone'
                    ? 'Invalid phone number or password.'
                    : 'Invalid email or password.',
            ])->withInput($request->except('password'));
        }

        $user = Auth::user();

        // Registration requests still awaiting admin approval cannot sign in yet.
        if ($user->isPendingApproval()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                $errorKey => 'Your registration is still awaiting approval by the Aquilala administrator. You will be able to sign in once it is approved.',
            ])->withInput($request->except('password'));
        }

        // Only accounts created through the Admin invitation flow are held
        // until they confirm via the emailed link. Developer/seeder-created
        // accounts (e.g. the platform Admin) and legacy self-signups are
        // active immediately.
        if ($user->isPendingVerification()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                $errorKey => 'Your account has not been activated yet. Please open the confirmation link sent to your email, or ask the administrator to resend the invitation.',
            ])->withInput($request->except('password'));
        }

        if ($user->status === \App\Models\User::STATUS_SUSPENDED) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                $errorKey => 'This account has been suspended. Please contact the administrator.',
            ])->withInput($request->except('password'));
        }

        return redirect()->route('dashboard.redirect', ['role' => $user->role]);
    }

    public function signup(Request $request)
    {
        // Production gets strict DNS and Spoof checking.
        // Local dev uses standard rfc format checking to avoid missing system extensions.
        $emailRule = app()->environment('production') ? 'email:rfc,dns,spoof' : 'email:rfc';

        $validated = $request->validate([
            'first_name'         => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
            'middle_initial'     => ['nullable', 'string', 'max:2', 'regex:/^[\pL\s\.]+$/u'],
            'last_name'          => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
            'sex'                => ['nullable', Rule::in(['Male', 'Female'])],
            'birth_date'         => ['nullable', 'date', 'before:today'],
            'civil_status'       => ['nullable', Rule::in(['Single', 'Married', 'Widowed', 'Separated'])],
            'email' => [
                'required',
                'string',
                'max:254',
                $emailRule,
                'unique:users,email',
            ],
            'phone' => [
                'required',
                'string',
                'max:25',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $normalized = \App\Services\UserInvitationService::normalizePhone((string) $value);

                    if ($normalized === null) {
                        $fail('Enter a valid Philippine mobile number — the 9 digits after +639.');
                        return;
                    }

                    if (User::where('phone', $normalized)->exists()) {
                        $fail('This phone number is already registered. Please use a different number or log in.');
                    }
                },
            ],
            'role' => ['required', Rule::in(['admin', 'store_owner', 'farmer'])],
            'address'           => ['nullable', 'string', 'max:500'],
            'barangay'          => ['nullable', 'string', 'max:100'],
            'city'              => ['nullable', 'string', 'max:100'],
            'province'          => ['nullable', 'string', 'max:100'],
            'farm_location'     => ['nullable', 'string', 'max:500'],
            'default_land_area' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'default_land_area_unit' => ['nullable', 'string', 'max:10'],
            'land_ownership'    => ['nullable', 'string', 'max:60'],
            'years_farming'     => ['nullable', 'integer', 'min:0', 'max:120'],
            'assoc_member_no'   => ['nullable', 'string', 'max:60'],
        ], [
            'first_name.required' => 'Please enter your first name.',
            'first_name.regex'    => 'First name may only contain letters, spaces, and hyphens.',
            'middle_initial.regex' => 'Middle initial may only contain letters, spaces, and periods.',
            'last_name.required'  => 'Please enter your last name.',
            'last_name.regex'     => 'Last name may only contain letters, spaces, and hyphens.',
            'sex.in'              => 'Select a valid option for sex.',
            'birth_date.before'   => 'Please enter a valid date of birth.',
            'civil_status.in'     => 'Select a valid civil status.',
            'email.required'      => 'Please enter your email address.',
            'email.email'         => 'Enter a valid email address.',
            'email.unique'        => 'This email is already registered. Please use a different email or log in.',
            'phone.required'      => 'Please enter your mobile number.',
            'phone.max'           => 'Mobile number is too long.',
            'role.in'             => 'Select a valid account role.',
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

        $user = User::create(array_filter([
            'name'           => $fullName,
            'first_name'     => $firstName,
            'middle_initial' => empty($mi) ? null : $mi,
            'last_name'      => $lastName,
            'sex'            => $validated['sex'] ?? null,
            'birth_date'     => $validated['birth_date'] ?? null,
            'civil_status'   => $validated['civil_status'] ?? null,
            'email'          => strtolower(trim($validated['email'])),
            'phone'          => \App\Services\UserInvitationService::normalizePhone($validated['phone']),
            'role'           => $validated['role'],
            'status'         => User::STATUS_PENDING_APPROVAL,
            // Random unusable password until the farmer sets their own via the
            // secure link emailed after an admin approves the registration.
            'password'       => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(64)),
            'address'        => $validated['address'] ?? null,
            'barangay'       => $validated['barangay'] ?? null,
            'city'           => $validated['city'] ?? null,
            'province'       => $validated['province'] ?? null,
            'farm_location'  => $validated['farm_location'] ?? null,
            'default_land_area'      => $validated['default_land_area'] ?? null,
            'default_land_area_unit' => $validated['default_land_area_unit'] ?? null,
            'land_ownership' => $validated['land_ownership'] ?? null,
            'years_farming'  => $validated['years_farming'] ?? null,
            'assoc_member_no'=> $validated['assoc_member_no'] ?? null,
        ], fn ($value) => $value !== null && $value !== ''));

        \App\Services\ActivityLogger::log(
            'user.registration_requested',
            "Submitted registration request for {$user->name} (farmer)",
            $user,
            ['phone' => $user->phone, 'email' => $user->email],
            $user
        );

        // Farmers do NOT auto-login. Their account waits for admin approval,
        // after which they receive a secure email link to set their password.
        return redirect()->route('register.submitted');
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
        $adminCount = User::where('role', 'admin')->count();
        $storeOwnersCount = User::where('role', 'store_owner')->count();
        $farmersCount = User::where('role', 'farmer')->count();

        // 2. Fetch the latest 10 profile registrations
        $recentUsers = User::latest()->take(10)->get();

        // ─── Admin Dashboard Metrics ──────────────────────────────
        // Platform catalogue & activity
        $totalCategories = Category::count();
        $totalProducts = Product::count();
        $activeProducts = Product::where('status', 'active')->count();
        $outOfStockProducts = Product::where('status', 'out_of_stock')
            ->orWhere('stock_quantity', 0)
            ->count();
        $discontinuedProducts = Product::where('status', 'discontinued')->count();

        $totalQuotations = Quotation::count();
        $quotationStatusCounts = [
            'pending'    => Quotation::where('status', Quotation::STATUS_PENDING)->count(),
            'computed'   => Quotation::where('status', Quotation::STATUS_COMPUTED)->count(),
            'dispatched' => Quotation::where('status', Quotation::STATUS_DISPATCHED)->count(),
            'accepted'   => Quotation::where('status', Quotation::STATUS_ACCEPTED)->count(),
            'rejected'   => Quotation::where('status', Quotation::STATUS_REJECTED)->count(),
        ];
        $pendingQuotations = $quotationStatusCounts['pending'];
        $acceptedQuotations = $quotationStatusCounts['accepted'];

        // Recent platform-wide quotation activity
        $recentPlatformQuotations = Quotation::with(['farmer', 'storeOwner'])
            ->latest()
            ->take(5)
            ->get();

        // Latest listings across the marketplace
        $recentProducts = Product::with(['storeOwner', 'category'])
            ->latest()
            ->take(5)
            ->get();

        // 3. Render the administration dashboard layout view safely with variables
        return view('dashboard.admin', compact(
            'totalUsers',
            'adminCount',
            'storeOwnersCount',
            'farmersCount',
            'recentUsers',
            'totalCategories',
            'totalProducts',
            'activeProducts',
            'outOfStockProducts',
            'discontinuedProducts',
            'totalQuotations',
            'quotationStatusCounts',
            'pendingQuotations',
            'acceptedQuotations',
            'recentPlatformQuotations',
            'recentProducts'
        ));
    }
}

