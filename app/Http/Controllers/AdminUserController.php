<?php

namespace App\Http\Controllers;

use App\Exceptions\Onboarding\DuplicateAccountException;
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
     * Show the Register User page.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Admin pre-registers a user account and triggers the free
     * email-link activation flow.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $emailRule = app()->environment('production') ? 'email:rfc,dns,spoof' : 'email:rfc';

        $validated = $request->validate([
            'first_name'     => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
            'middle_initial' => ['nullable', 'string', 'max:2', 'regex:/^[\pL\s\.]+$/u'],
            'last_name'      => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
            'email'          => ['required', 'string', 'max:254', $emailRule],
            'phone'          => ['required', 'string', 'max:25'],
            'role'           => ['required', Rule::in(['farmer', 'store_owner'])],
            'barangay'       => ['nullable', 'string', 'max:120'],
            'city'           => ['nullable', 'string', 'max:120'],
            'province'       => ['nullable', 'string', 'max:120'],
        ], [
            'first_name.required' => 'Please enter the user\'s first name.',
            'first_name.regex'    => 'First name may only contain letters, spaces, and hyphens.',
            'last_name.required'  => 'Please enter the user\'s last name.',
            'last_name.regex'     => 'Last name may only contain letters, spaces, and hyphens.',
            'email.required'      => 'Please enter the user\'s email address.',
            'email.email'         => 'Enter a valid email address.',
            'phone.required'      => 'Please enter the user\'s mobile number.',
            'role.required'       => 'Select an account role.',
            'role.in'             => 'Select a valid account role.',
        ]);

        try {
            $user = UserInvitationService::invite($validated);
        } catch (DuplicateAccountException $e) {
            return $this->invitationFailure($request, $e->getMessage(), $e->field, 422);
        } catch (MailDeliveryException $e) {
            // Infrastructure failure, not a user-input problem.
            return $this->invitationFailure($request, $e->getMessage(), 'email', 503);
        }

        $message = "Invitation sent to {$user->email}. The account activates once the user confirms via the emailed link.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 201);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', $message);
    }

    /**
     * Show the registration approvals page: all submitted registration
     * requests waiting for the admin to review.
     */
    public function approvals()
    {
        $applications = \App\Models\User::with('activations')
            ->where('role', 'farmer')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.approvals.index', compact('applications'));
    }

    /**
     * Approve a pending registration request: flip the account to
     * pending_verification and email a secure link to set the password.
     */
    public function approve(Request $request, User $user): JsonResponse|RedirectResponse
    {
        try {
            UserInvitationService::approveRegistration($user);
        } catch (MailDeliveryException $e) {
            return $this->approvalFailure($request, $e->getMessage(), 503);
        } catch (\App\Exceptions\Onboarding\InvalidActivationTokenException $e) {
            return $this->approvalFailure($request, $e->getMessage(), 409);
        }

        $message = "{$user->name}'s registration was approved. A secure link to set their password was emailed to {$user->email}.";

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
        } catch (\App\Exceptions\Onboarding\InvalidActivationTokenException $e) {
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
     * Re-send the activation email for an account still pending verification.
     */
    public function resend(Request $request, User $user): JsonResponse|RedirectResponse
    {
        if (!$user->isPendingVerification()) {
            $message = "{$user->name} has already activated their account.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 409);
            }

            return back()->withErrors(['resend' => $message]);
        }

        try {
            UserInvitationService::resendInvitation($user);
        } catch (MailDeliveryException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 503);
            }

            return back()->withErrors(['resend' => $e->getMessage()]);
        }

        $message = "A new activation link was sent to {$user->email}. The previous link is now void.";

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
                'errors'  => [$field => [$message]],
            ], $status);
        }

        return back()->withErrors([$field => $message])->withInput();
    }
}
