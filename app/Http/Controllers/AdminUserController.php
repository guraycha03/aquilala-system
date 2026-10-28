<?php

namespace App\Http\Controllers;

use App\Exceptions\Onboarding\DuplicateAccountException;
use App\Exceptions\Onboarding\InvalidActivationTokenException;
use App\Exceptions\Onboarding\MailDeliveryException;
use App\Mail\AdminAccessGrantedMail;
use App\Services\AdminCreationKeyService;
use App\Models\ActivityLog;
use App\Models\Member;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\UserInvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
                    'photo' => $u->profilePhotoUrl(),
                    'initials' => $u->initials(),
                ],
            ];
        })->all();

        return view('admin.users.index', compact(
            'users', 'totalUsers', 'ownerCount', 'farmerCount', 'adminCount',
            'activatedCount', 'pendingCount', 'disabledCount', 'roleMeta', 'profileData'
        ));
    }

    /**
     * Archive selected accounts without permanently deleting them.
     */
    public function archiveBulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'string'],
        ]);

        $ids = collect(explode(',', $validated['ids']))
            ->map(fn ($id) => trim($id))
            ->filter(fn ($id) => ctype_digit($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return back()->with('error', 'Select at least one account to archive.');
        }

        $currentUserId = $request->user()->id;
        $archived = User::whereIn('id', $ids)
            ->whereKeyNot($currentUserId)
            ->delete();

        $skippedSelf = $ids->contains($currentUserId);
        $message = "{$archived} account".($archived === 1 ? '' : 's').' archived.';
        if ($skippedSelf) {
            $message .= ' Your own administrator account was not archived.';
        }

        return back()->with('success', $message);
    }

    /**
     * Step 0 of account creation: what kind of account is it?
     *
     *   - (no type)          -> the chooser itself, three cards
     *   - ?type=farmer       -> the member picker (accounts are made *from*
     *                           a person already on the members list)
     *   - ?member={id}       -> the confirmation form for that one member
     *   - ?type=admin        -> the typed form (administrators are not
     *                           association members, so there is no record
     *                           to pick them from)
     *   - ?type=store_owner  -> handed to the Stores page, which owns the
     *                           store registration form
     *
     * With ?member={id} the picker is skipped and only that one member's
     * confirmation form is shown.
     */
    public function create(Request $request)
    {
        $memberId = $request->query('member');

        if ($memberId !== null && $memberId !== '') {
            $member = Member::available()->find($memberId);

            if ($member === null) {
                return redirect()
                    ->route('admin.users.create', ['type' => 'farmer'])
                    ->with('error', 'That member either does not exist or already has an account. Choose someone from the list instead.');
            }

            if (! $member->isActive()) {
                return redirect()
                    ->route('admin.users.create', ['type' => 'farmer'])
                    ->with('error', "{$member->fullName()}'s member record is inactive. Reactivate it on the Members page before creating an account.");
            }

            return view('admin.users.create', ['member' => $member, 'members' => collect()]);
        }

        $type = $request->query('type');

        if ($type === 'admin') {
            if (! $this->adminCreationIsVerified($request)) {
                return view('admin.users.create-admin-gate');
            }

            return view('admin.users.create-admin');
        }

        if ($type === 'store_owner') {
            return redirect()->route('admin.stores.create');
        }

        if ($type !== 'farmer') {
            return view('admin.users.choose');
        }

        // Everyone still waiting for sign-in access, newest records last so
        // the list reads in the same order as the Members page.
        $members = Member::available()
            ->where('status', Member::STATUS_ACTIVE)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('name')
            ->get();

        return view('admin.users.create', ['member' => null, 'members' => $members]);
    }

    public function requestAdminCreationKey(Request $request): RedirectResponse
    {
        $admin = $request->user();
        abort_unless($admin !== null && $admin->role === 'admin', 403);

        $validated = $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Enter your current administrator password.',
        ]);

        if (! Hash::check($validated['password'], (string) $admin->password)) {
            return back()->withErrors(['password' => 'Your password is incorrect.']);
        }

        try {
            $issued = AdminCreationKeyService::issue($admin);
            AdminCreationKeyService::send($admin, $issued['key']);
        } catch (MailDeliveryException $e) {
            return back()->withErrors(['password' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.users.create', ['type' => 'admin', 'step' => 'verify'])
            ->with('success', "A one-time administrator creation key was sent to {$admin->email}.");
    }

    public function verifyAdminCreationKey(Request $request): RedirectResponse
    {
        $admin = $request->user();
        abort_unless($admin !== null && $admin->role === 'admin', 403);

        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100'],
        ], [
            'key.required' => 'Enter the one-time key from your email.',
        ]);

        if (! AdminCreationKeyService::verify($admin, $validated['key'])) {
            return back()->withErrors(['key' => 'That key is invalid, expired, already used, or has too many failed attempts. Request a new key.']);
        }

        $request->session()->put('admin_creation_verified_until', now()->addMinutes(AdminCreationKeyService::TTL_MINUTES)->timestamp);

        return redirect()->route('admin.users.create', ['type' => 'admin']);
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
     * Grant administrator access to an existing account.
     *
     * This is the only in-app way to make an administrator — the terminal
     * command that produced the first one is deliberately one-shot. It is
     * kept out of the profile form on purpose so it is never submitted by
     * accident, and it carries the four controls you would expect of a
     * privilege escalation in a real system:
     *
     *   1. Step-up authentication — the acting admin re-enters their own
     *      password, so an unattended signed-in session cannot mint one.
     *   2. Explicit consent — a checkbox that states what is being granted.
     *   3. Rate limiting — `throttle:admin-grant`, 5 attempts/minute per
     *      account, so the password field cannot be brute-forced.
     *   4. An audit row naming both sides (target owns the row, granting
     *      admin is the actor), plus a notification email to the target.
     *
     * Only active accounts qualify: a still-pending invitee has not proved
     * who they are yet.
     */
    public function promote(Request $request, User $user): RedirectResponse
    {
        $granter = $request->user();

        // The route already sits behind role:admin, but the invariant belongs
        // in the handler too — this is not a check to leave to middleware.
        abort_unless($granter !== null && $granter->role === 'admin', 403);

        if ($user->id === $granter->id) {
            return back()->with('error', 'You already have administrator access.');
        }

        if ($user->role === 'admin') {
            return back()->with('error', "{$user->name} is already an administrator.");
        }

        if (! $user->isActive()) {
            return back()->with(
                'error',
                "{$user->name}'s account is still {$user->status}. Only an active account can be granted administrator access."
            );
        }

        $request->validate([
            'password' => ['required', 'string'],
            'confirm' => ['accepted'],
        ], [
            'password.required' => 'Enter your password to confirm this change.',
            'confirm.accepted' => 'Tick the box to confirm you understand what this grants.',
        ]);

        if (! Hash::check((string) $request->input('password'), $granter->password)) {
            return back()
                ->withErrors(['password' => 'Your password is incorrect.'])
                ->withInput($request->except('password'));
        }

        $before = $user->role;

        DB::transaction(function () use ($user, $granter, $before): void {
            $user->update(['role' => 'admin']);

            ActivityLogger::log(
                ActivityLog::TYPE_ADMIN_PROMOTED,
                "Administrator access granted to {$user->name} by {$granter->name}",
                $user,
                [
                    'email' => $user->email,
                    'role_before' => $before,
                    'role_after' => 'admin',
                    'granted_by' => $granter->email,
                    'granted_by_ip' => request()->ip(),
                ],
                $user,
                $granter
            );
        });

        // The database change is the source of truth; a mail server being down
        // must not roll a privilege grant back. Report and carry on.
        $notified = true;

        try {
            Mail::to($user->email)->send(new AdminAccessGrantedMail($user, $granter));
        } catch (\Throwable $e) {
            $notified = false;
            report($e);
        }

        $message = "{$user->name} now has administrator access.";

        if (! $notified) {
            $message .= ' The confirmation email could not be sent — ask them to check their inbox later.';
        }

        return redirect()->route('admin.users.edit', $user)->with('success', $message);
    }

    /**
     * Create the account for a chosen member and email them their 6-digit
     * activation code.
     *
     * Every identifying field — name, address, phone, farming details — comes
     * off the member record. The administrator only supplies an email address
     * (plus a phone override when the member has none) because those are what
     * sign-in and the invitation are keyed to.
     *
     * The role is always "farmer": store owners are not association members
     * and are set up through the store flow instead.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $emailRule = app()->environment('production') ? 'email:rfc,dns,spoof' : 'email:rfc';

        $validated = $request->validate([
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')->whereNull('user_id')],
            'email' => ['required', 'string', 'max:254', $emailRule, Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:25'],
        ], [
            'member_id.required' => 'Choose a member to create the account for.',
            'member_id.exists' => 'That member either does not exist or already has an account.',
            'email.required' => 'An email address is required — it is how the member signs in.',
            'email.unique' => 'That email address is already in use by another account.',
            'email.email' => 'Enter a valid email address.',
        ]);

        $member = Member::find($validated['member_id']);

        if ($member === null || ! $member->isActive()) {
            $who = $member?->fullName() ?? 'That member';

            return $this->invitationFailure(
                $request,
                "{$who} is not an active member. Reactivate their member record before creating an account.",
                'member_id',
                422
            );
        }

        // The form pre-fills the member's own number, but the server still
        // falls back to the record when the field never arrives — the list is
        // the source of truth for contact details.
        $phone = $request->has('phone')
            ? trim((string) $request->input('phone'))
            : (string) ($member->phone ?? '');

        $data = array_merge($this->nameParts($member), [
            'email' => $validated['email'],
            'phone' => $phone !== '' ? $phone : null,
            'role' => 'farmer',
            'barangay' => $member->barangay,
            'city' => $member->city,
            'province' => $member->province,
        ]);

        try {
            $delivery = [];
            $user = UserInvitationService::invite($data, $delivery);
        } catch (DuplicateAccountException $e) {
            return $this->invitationFailure($request, $e->getMessage(), $e->field, 422);
        } catch (MailDeliveryException $e) {
            // Infrastructure failure, not a user-input problem.
            return $this->invitationFailure($request, $e->getMessage(), 'email', 503);
        }

        // Mirror the rest of the member profile onto the account and link the
        // two records together so the Users page still shows the same details
        // it always did — and so this member cannot be picked a second time.
        try {
            DB::transaction(function () use ($user, $member) {
                $user->forceFill([
                    'assoc_member_no' => $member->assoc_member_no,
                    'sex' => $member->sex,
                    'birth_date' => $member->birth_date,
                    'civil_status' => $member->civil_status,
                    'farm_location' => $member->farm_location,
                    'land_ownership' => $member->land_ownership,
                    'years_farming' => $member->years_farming,
                ])->save();

                $member->update(['user_id' => $user->id]);
            });
        } catch (\Throwable $e) {
            report($e);

            return $this->invitationFailure(
                $request,
                "The account was created but could not be linked back to {$member->fullName()}'s member record. Contact support before retrying, or this member will appear available twice.",
                'member_id',
                500
            );
        }

        $message = $delivery && empty($delivery['sent'])
            ? "{$user->name}'s account was created, but the activation email could not be sent — {$delivery['error']} To deliver invitations, verify a sender domain at resend.com and set MAIL_FROM_ADDRESS in .env, then click Resend Invitation in the Users list."
            : "Account created for {$user->name} from the members list. They can now sign in, enter the 6-digit activation code we emailed them, and set their own password.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 201);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', $message);
    }

    /**
     * Create a brand-new administrator account from typed details.
     *
     * Administrators are not association members, so unlike farmers they
     * cannot be picked off the members list — this is the one place in the
     * app where an account is typed in from scratch. It therefore carries
     * the same four controls as granting admin to an existing account (see
     * promote()):
     *
     *   1. Step-up authentication — the acting admin re-enters their own
     *      password, so an unattended session cannot mint an administrator.
     *   2. Explicit consent — a checkbox that states what is being granted.
     *   3. Rate limiting — `throttle:admin-grant`, 5 attempts/minute, so
     *      the password field cannot be brute-forced.
     *   4. An audit row naming both sides (the new admin owns the row, the
     *      creator is the actor).
     *
     * The account goes out pending with a 6-digit activation code, exactly
     * like a farmer account, so the new administrator still chooses their
     * own password — nothing typed here becomes a sign-in secret.
     */
    public function storeAdmin(Request $request): RedirectResponse
    {
        $granter = $request->user();

        // The route sits behind role:admin, but the invariant belongs here too.
        abort_unless($granter !== null && $granter->role === 'admin', 403);

        if (! $this->adminCreationIsVerified($request)) {
            return redirect()
                ->route('admin.users.create', ['type' => 'admin'])
                ->withErrors(['key' => 'Verify the emailed administrator creation key before submitting this form.']);
        }

        $emailRule = app()->environment('production') ? 'email:rfc,dns,spoof' : 'email:rfc';

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'middle_initial' => ['nullable', 'string', 'max:10'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'max:254', $emailRule, Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:25'],
            'password' => ['required', 'string'],
            'confirm' => ['accepted'],
        ], [
            'first_name.required' => "Enter the administrator's first name.",
            'last_name.required' => "Enter the administrator's last name.",
            'email.required' => 'An email address is required — it is how they sign in.',
            'email.unique' => 'That email address is already in use by another account.',
            'email.email' => 'Enter a valid email address.',
            'password.required' => 'Enter your password to confirm this change.',
            'confirm.accepted' => 'Tick the box to confirm you understand what this grants.',
        ]);

        // Step-up authentication: an unattended signed-in session cannot
        // mint an administrator — prove it is really you first.
        if (! Hash::check((string) $validated['password'], $granter->password)) {
            return back()
                ->withErrors(['password' => 'Your password is incorrect.'])
                ->withInput($request->except('password'));
        }

        $middle = trim((string) ($validated['middle_initial'] ?? ''));
        $phone = trim((string) ($validated['phone'] ?? ''));

        try {
            $delivery = [];
            $user = UserInvitationService::invite([
                'first_name' => trim($validated['first_name']),
                'middle_initial' => $middle !== '' ? $middle : null,
                'last_name' => trim($validated['last_name']),
                'email' => $validated['email'],
                'phone' => $phone !== '' ? $phone : null,
                'role' => 'admin',
            ], $delivery);
        } catch (DuplicateAccountException $e) {
            return back()
                ->withErrors([$e->field => $e->getMessage()])
                ->withInput($request->except('password'));
        } catch (MailDeliveryException $e) {
            return back()
                ->withErrors(['email' => $e->getMessage()])
                ->withInput($request->except('password'));
        }

        // invite() already wrote the user.invited row; this one is the
        // privilege audit — who created the administrator, from where.
        ActivityLogger::log(
            ActivityLog::TYPE_ADMIN_CREATED,
            "Administrator account created for {$user->name} by {$granter->name}",
            $user,
            [
                'email' => $user->email,
                'role' => 'admin',
                'created_by' => $granter->email,
                'created_by_ip' => request()->ip(),
            ],
            $user,
            $granter
        );

        $message = $delivery && empty($delivery['sent'])
            ? "{$user->name}'s administrator account was created, but the activation email could not be sent — {$delivery['error']} Use Resend Invitation on the Users list once a sender domain is configured."
            : "{$user->name}'s administrator account was created. They can now sign in with {$user->email}, enter the 6-digit activation code we emailed them, and set their own password.";

        $request->session()->forget('admin_creation_verified_until');

        return redirect()
            ->route('admin.users.index')
            ->with('success', $message);
    }

    /**
     * Split a member's name into the three fields the account needs.
     *
     * Members imported from a single "Full Name" column may only carry one
     * unbroken string, so fall back to the first and last tokens of it rather
     * than rejecting the record.
     *
     * @return array{first_name: string, middle_initial: ?string, last_name: string}
     */
    private function nameParts(Member $member): array
    {
        $first = trim((string) $member->first_name);
        $last = trim((string) $member->last_name);
        $mi = trim((string) $member->middle_initial);

        if ($first === '' && $last === '') {
            $tokens = preg_split('/\s+/', trim((string) $member->name)) ?: [];
            $first = array_shift($tokens) ?? '';
            $last = array_pop($tokens) ?? '';

            // A single-word name has no surname to give — leave it blank
            // rather than repeating the given name twice.
            if ($tokens === []) {
                $last = '';
            }
        }

        return [
            'first_name' => $first,
            'middle_initial' => $mi !== '' ? $mi : null,
            'last_name' => $last,
        ];
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

    private function adminCreationIsVerified(Request $request): bool
    {
        $until = (int) $request->session()->get('admin_creation_verified_until', 0);

        if ($until <= now()->timestamp) {
            $request->session()->forget('admin_creation_verified_until');

            return false;
        }

        return true;
    }
}
