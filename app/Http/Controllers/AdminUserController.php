<?php

namespace App\Http\Controllers;

use App\Exceptions\Onboarding\DuplicateAccountException;
use App\Exceptions\Onboarding\InvalidActivationTokenException;
use App\Exceptions\Onboarding\MailDeliveryException;
use App\Models\User;
use App\Services\UserInvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * List every account on the platform with live profile data.
     */
    public function index()
    {
        $users = User::withCount('products')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalUsers = User::count();
        $ownerCount = User::where('role', 'store_owner')->count();
        $farmerCount = User::where('role', 'farmer')->count();
        $adminCount = User::where('role', 'admin')->count();
        $activatedCount = User::where('status', User::STATUS_ACTIVE)->count();
        $pendingCount = User::where('status', User::STATUS_PENDING)->count();
        $disabledCount = User::where('status', User::STATUS_DISABLED)->count();

        $roleMeta = [
            'admin' => ['Administrator', '#86799f', '#f1eef6'],
            'store_owner' => ['Store Owner', '#a57f43', '#f6f0e6'],
            'farmer' => ['Farmer', '#63856c', '#eef3ef'],
        ];

        $profileData = $users->mapWithKeys(function ($u) {
            return [
                $u->id => [
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => ucwords(str_replace('_', ' ', $u->role)),
                    'status' => $u->status,
                    'phone' => $u->phone,
                    'barangay' => $u->barangay,
                    'city' => $u->city,
                    'province' => $u->province,
                    'sex' => $u->sex,
                    'birth_date' => $u->birth_date?->format('M d, Y'),
                    'civil_status' => $u->civil_status,
                    'farm_location' => $u->farm_location,
                    'land_ownership' => $u->land_ownership,
                    'years_farming' => $u->years_farming,
                    'assoc_member_no' => $u->assoc_member_no,
                    'must_change_password' => $u->mustChangePassword(),
                    'joined' => $u->created_at?->format('M d, Y'),
                    'products' => $u->products_count,
                ],
            ];
        })->all();

        return view('admin.users.index', compact(
            'users', 'totalUsers', 'ownerCount', 'farmerCount', 'adminCount',
            'activatedCount', 'pendingCount', 'disabledCount', 'roleMeta', 'profileData'
        ));
    }

    /**
     * Show the Register User page.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Show the Edit User page for an existing account.
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    /**
     * Update an existing account's profile, role, or status.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:254', 'email:rfc', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:25', Rule::unique('users', 'phone')->ignore($user->id)],
            'role' => ['nullable', Rule::in(['farmer', 'store_owner'])],
            'status' => ['nullable', Rule::in([
                User::STATUS_ACTIVE,
                User::STATUS_PENDING,
                User::STATUS_DISABLED,
            ])],
            'barangay' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
        ], [
            'name.required' => 'Please enter the user\'s full name.',
            'email.required' => 'Please enter the user\'s email address.',
            'email.unique' => 'That email address is already in use by another account.',
            'phone.unique' => 'That phone number is already in use by another account.',
            'email.email' => 'Enter a valid email address.',
        ]);

        // Role is only reassignable for non-admin accounts.
        if (empty($validated['role']) && $user->role === 'admin') {
            $validated['role'] = 'admin';
        }

        // Normalise blank contact/address fields to NULL so the unique
        // phone index can hold multiple blank rows.
        foreach (['phone', 'barangay', 'city', 'province'] as $field) {
            if (isset($validated[$field]) && trim((string) $validated[$field]) === '') {
                $validated[$field] = null;
            }
        }

        $fill = function (string $field) use ($validated, $user) {
            return array_key_exists($field, $validated)
                ? $validated[$field]
                : $user->getAttribute($field);
        };

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $fill('phone'),
            'role' => $fill('role'),
            'status' => $fill('status'),
            'barangay' => $fill('barangay'),
            'city' => $fill('city'),
            'province' => $fill('province'),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "{$user->name}'s profile was updated.");
    }

    /**
     * Admin pre-registers a user account and emails a single-use 6-digit
     * activation code plus the entry link (AccountActivationController).
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $emailRule = app()->environment('production') ? 'email:rfc,dns,spoof' : 'email:rfc';

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
            'middle_initial' => ['nullable', 'string', 'max:50', 'regex:/^[\pL\s\.]+$/u'],
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
            'email' => ['required', 'string', 'max:254', $emailRule],
            'phone' => ['nullable', 'string', 'max:25'],
            'role' => ['required', Rule::in(['farmer', 'store_owner'])],
            'barangay' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
        ], [
            'first_name.required' => 'Please enter the user\'s first name.',
            'first_name.regex' => 'First name may only contain letters, spaces, and hyphens.',
            'last_name.required' => 'Please enter the user\'s last name.',
            'last_name.regex' => 'Last name may only contain letters, spaces, and hyphens.',
            'email.required' => 'Please enter the user\'s email address.',
            'email.email' => 'Enter a valid email address.',
            'role.required' => 'Select an account role.',
            'role.in' => 'Select a valid account role.',
        ]);

        try {
            $delivery = [];
            $user = UserInvitationService::invite($validated, $delivery);
        } catch (DuplicateAccountException $e) {
            return $this->invitationFailure($request, $e->getMessage(), $e->field, 422);
        } catch (MailDeliveryException $e) {
            // Infrastructure failure, not a user-input problem.
            return $this->invitationFailure($request, $e->getMessage(), 'email', 503);
        }

        $message = $delivery && empty($delivery['sent'])
            ? "{$user->name}'s account was created, but the activation email could not be sent — {$delivery['error']} To deliver invitations, verify a sender domain at resend.com and set MAIL_FROM_ADDRESS in .env, then click Resend Invitation in the Users list."
            : "Account created for {$user->email}. They can now sign in, enter the 6-digit activation code we emailed them, and set their own password.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 201);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', $message);
    }

    /**
     * Show the approvals page: accounts awaiting activation.
     *
     * Includes self-registered farmers and any admin-created account still in
     * the pending state.
     */
    public function approvals()
    {
        $applications = User::with('activations')
            ->where('role', 'farmer')
            ->where('status', User::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.approvals.index', compact('applications'));
    }

    /**
     * Approve a pending registration request: mark it approved and email a
     * fresh activation code to set the password.
     */
    public function approve(Request $request, User $user): JsonResponse|RedirectResponse
    {
        try {
            UserInvitationService::approveRegistration($user);
        } catch (MailDeliveryException $e) {
            return $this->approvalFailure($request, $e->getMessage(), 503);
        } catch (InvalidActivationTokenException $e) {
            return $this->approvalFailure($request, $e->getMessage(), 409);
        }

        $message = "{$user->name}'s registration was approved. A verification code was emailed to {$user->email}.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('admin.approvals.index')->with('success', $message);
    }

    /**
     * Reject a pending registration request by deleting the account.
     */
    public function reject(Request $request, User $user): JsonResponse|RedirectResponse
    {
        try {
            UserInvitationService::rejectRegistration($user);
        } catch (InvalidActivationTokenException $e) {
            return $this->approvalFailure($request, $e->getMessage(), 409);
        }

        $message = "{$user->name}'s registration request was rejected.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('admin.approvals.index')->with('success', $message);
    }

    private function approvalFailure(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()->route('admin.approvals.index')->with('error', $message);
    }

    /**
     * Re-issue an invitation for an account that has not finished setting up.
     *
     * Pending accounts get a fresh 6-digit activation code. Bulk-imported
     * accounts (active but still holding their temporary password) get a fresh
     * temporary password and set-password link instead.
     */
    public function resend(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $alreadySetUp = $user->isActive() && ! $user->mustChangePassword();

        if ($alreadySetUp || $user->isDisabled()) {
            $message = $alreadySetUp
                ? "{$user->name} has already activated their account."
                : "{$user->name} is disabled and cannot receive an invitation.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 409);
            }

            return back()->with('error', $message);
        }

        try {
            if ($user->isPending()) {
                UserInvitationService::issueActivation($user);
                $noun = 'activation code';
            } else {
                UserInvitationService::issueSetPassword($user);
                $noun = 'password-setup invitation';
            }
        } catch (MailDeliveryException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 503);
            }

            return back()->with('error', $e->getMessage());
        }

        $message = "A new {$noun} was sent to {$user->email}. Any previously issued one is now void.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }

    private function invitationFailure(Request $request, string $message, string $field, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => [$field => [$message]],
            ], $status);
        }

        return back()->withErrors([$field => $message])->withInput();
    }
}
