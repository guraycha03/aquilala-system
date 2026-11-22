<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ImportUpload;
use App\Models\Member;
use App\Services\ActivityLogger;
use App\Services\UserInvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The association's list of members.
 *
 * Administrators enter people here (or import them from a file / scan).
 * Nothing on this controller creates an account — that only ever happens by
 * picking a member off this list, in AdminUserController.
 */
class AdminMemberController extends Controller
{
    /**
     * Members with live search, status and account filters.
     *
     * The whole list is handed to the view rather than a page of it: the
     * shared table toolbar does its searching, filtering and paging in the
     * browser, so it needs every row present in the DOM — the same contract
     * the accounts table already works to.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $account = (string) $request->query('account', '');

        $members = Member::query()
            ->search($q)
            ->when(
                in_array($status, [Member::STATUS_ACTIVE, Member::STATUS_INACTIVE], true),
                fn ($query) => $query->where('status', $status)
            )
            ->when($account === 'none', fn ($query) => $query->whereNull('user_id'))
            ->when($account === 'linked', fn ($query) => $query->whereNotNull('user_id'))
            ->with('user:id,name,role,status,email')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => Member::count(),
            'active' => Member::where('status', Member::STATUS_ACTIVE)->count(),
            'inactive' => Member::where('status', Member::STATUS_INACTIVE)->count(),
            'without_account' => Member::whereNull('user_id')->count(),
            'with_account' => Member::whereNotNull('user_id')->count(),
        ];

        // The Uploads button on this page opens an inline panel rather than
        // a second page. The archive is still the members importer's own files
        // — the exact ones the list above was built from.
        $uploads = ImportUpload::query()
            ->where('kind', ImportUpload::KIND_MEMBERS)
            ->with('user')
            ->withCount(['harvestReports as records_present'])
            ->latest()
            ->get();

        return view('admin.members.index', compact('members', 'stats', 'q', 'status', 'account', 'uploads'));
    }

    /**
     * Archive selected member records without permanently deleting them.
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
            return back()->with('error', 'Select at least one member to archive.');
        }

        $archived = Member::whereIn('id', $ids)->delete();

        return back()->with(
            'success',
            "{$archived} member".($archived === 1 ? '' : 's').' archived.'
        );
    }

    /**
     * Blank form for adding one member by hand.
     */
    public function create()
    {
        return view('admin.members.create');
    }

    /**
     * Store one manually entered member.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $emailRule = app()->environment('production') ? 'email:rfc,dns,spoof' : 'email:rfc';

        $validated = $request->validate($this->rules($emailRule), $this->messages());

        $validated = $this->clean($validated);

        // The create form shows city and province fixed to the association's
        // home town — whatever was posted is replaced here. Editing (see
        // update()) keeps whatever is on the record.
        $validated['city'] = config('bulan.city');
        $validated['province'] = config('bulan.province');

        $member = Member::create([
            ...$validated,
            'name' => $this->composeName($validated),
            'status' => $validated['status'] ?? Member::STATUS_ACTIVE,
            'created_by' => $request->user()?->id,
        ]);

        ActivityLogger::log(
            ActivityLog::TYPE_MEMBER_CREATED,
            "Added {$member->fullName()} to the members list",
            $member,
            ['member_no' => $member->assoc_member_no]
        );

        $message = "{$member->fullName()} was added to the members list.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'member' => $member], 201);
        }

        return redirect()
            ->route('admin.members.index')
            ->with('success', $message);
    }

    /**
     * Edit an existing member's record.
     */
    public function edit(Member $member)
    {
        return view('admin.members.edit', ['member' => $member]);
    }

    /**
     * Show all information for a member.
     */
    public function show(Member $member)
    {
        $member->load(['user:id,name,email,role,status', 'createdBy:id,name']);

        return view('admin.members.show', ['member' => $member]);
    }

    /**
     * Persist changes to a member's record.
     *
     * Their account (if any) is left alone — this edits the person, not the
     * login. Contact details are re-applied to the account only where the
     * account cannot drift out of step with the list.
     */
    public function update(Request $request, Member $member): RedirectResponse
    {
        $emailRule = app()->environment('production') ? 'email:rfc,dns,spoof' : 'email:rfc';

        $validated = $request->validate(
            $this->rules($emailRule, $member),
            $this->messages()
        );

        $validated = $this->clean($validated);

        $wasLinked = $member->user_id !== null;

        $member->update([
            ...$validated,
            'name' => $this->composeName($validated, $member),
            'status' => $validated['status'] ?? $member->status,
        ]);

        if ($member->user) {
            $member->user->forceFill([
                'default_land_area' => $member->land_area,
                'default_land_area_unit' => $member->land_area_unit,
            ])->save();
        }

        ActivityLogger::log(
            ActivityLog::TYPE_MEMBER_UPDATED,
            "Updated {$member->fullName()}'s member record",
            $member,
            ['was_linked' => $wasLinked]
        );

        return redirect()
            ->route('admin.members.index')
            ->with('success', "{$member->fullName()}'s record was updated.");
    }

    /**
     * Remove a member who has never been given a system account.
     *
     * Members who already hold one are protected: the account would be left
     * pointing at nothing, so the admin has to deal with the account first.
     */
    public function destroy(Request $request, Member $member): JsonResponse|RedirectResponse
    {
        if ($member->user_id !== null) {
            $message = "{$member->fullName()} already has a system account. Remove or disable that account first.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 409);
            }

            return back()->with('error', $message);
        }

        $name = $member->fullName();
        $member->delete();

        ActivityLogger::log(
            ActivityLog::TYPE_MEMBER_DELETED,
            "Removed {$name} from the members list",
            null,
            ['member_no' => $member->assoc_member_no]
        );

        $message = "{$name} was removed from the members list.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()
            ->route('admin.members.index')
            ->with('success', $message);
    }

    /**
     * Validation rules shared by create and edit. `$except` skips the rules
     * that would trip over the record being edited.
     *
     * @return array<string, array<int, mixed>>
     */
    private function rules(string $emailRule, ?Member $except = null): array
    {
        return [
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
            'middle_initial' => ['nullable', 'string', 'max:50', 'regex:/^[\pL\s\.]+$/u'],
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
            'sex' => ['nullable', 'string', Rule::in(['Male', 'Female'])],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'civil_status' => ['nullable', 'string', Rule::in(['Single', 'Married', 'Widowed', 'Separated'])],
            'email' => ['nullable', 'string', 'max:254', $emailRule, Rule::unique('members', 'email')->ignore($except?->id)],
            'phone' => ['nullable', 'string', 'max:25', Rule::unique('members', 'phone')->ignore($except?->id)],
            'address' => ['nullable', 'string', 'max:500'],
            // On create the form only offers Bulan's barangays (see
            // config/bulan.php) — hold the same line server-side. Editing
            // keeps legacy addresses working.
            'barangay' => $except === null
                ? ['nullable', 'string', 'max:100', Rule::in(config('bulan.barangays'))]
                : ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'farm_location' => ['nullable', 'string', 'max:500'],
            'land_ownership' => ['nullable', 'string', Rule::in(['Owned', 'Rented', 'Shared', 'Communal'])],
            'land_area' => $except === null
                ? ['required', 'numeric', 'min:0.01', 'max:999999.99']
                : ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'land_area_unit' => $except === null
                ? ['required', Rule::in(['ha', 'sq_m', 'sq_ft'])]
                : ['nullable', Rule::in(['ha', 'sq_m', 'sq_ft'])],
            'years_farming' => ['nullable', 'integer', 'min:0', 'max:80'],
            'assoc_member_no' => ['nullable', 'string', 'max:60', Rule::unique('members', 'assoc_member_no')->ignore($except?->id)],
            'status' => ['nullable', Rule::in([Member::STATUS_ACTIVE, Member::STATUS_INACTIVE])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'first_name.required' => 'Please enter the member\'s first name.',
            'first_name.regex' => 'First name may only contain letters, spaces, and hyphens.',
            'last_name.required' => 'Please enter the member\'s last name.',
            'last_name.regex' => 'Last name may only contain letters, spaces, and hyphens.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'Another member already uses that email address.',
            'phone.unique' => 'Another member already uses that phone number.',
            'assoc_member_no.unique' => 'That association member number is already taken.',
            'barangay.in' => 'Choose a barangay of Bulan, Sorsogon from the list.',
            'years_farming.integer' => 'Years farming must be a whole number.',
            'years_farming.min' => 'Years farming cannot be negative.',
            'land_area.required' => 'Please enter the member\'s total land area.',
            'land_area.numeric' => 'Land area must be a number.',
            'land_area.min' => 'Land area must be greater than zero.',
            'birth_date.before' => 'Birth date must be in the past.',
        ];
    }

    /**
     * Normalise what comes in: blank contacts become NULL (so the unique
     * indexes hold), the phone is rewritten to the stored PH format, and
     * whitespace is collapsed.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function clean(array $validated): array
    {
        foreach (['email', 'phone', 'address', 'barangay', 'city', 'province', 'farm_location', 'notes', 'assoc_member_no'] as $field) {
            if (array_key_exists($field, $validated) && is_string($validated[$field])) {
                $validated[$field] = trim(preg_replace('/\s+/u', ' ', $validated[$field]));
            }
        }

        if (array_key_exists('email', $validated)) {
            $validated['email'] = ($validated['email'] === null || $validated['email'] === '')
                ? null
                : strtolower($validated['email']);
        }

        if (isset($validated['phone']) && $validated['phone'] !== null && $validated['phone'] !== '') {
            $normalized = UserInvitationService::normalizePhone($validated['phone']);
            $validated['phone'] = $normalized;
        } elseif (isset($validated['phone'])) {
            $validated['phone'] = null;
        }

        foreach (['address', 'barangay', 'city', 'province', 'farm_location', 'assoc_member_no'] as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        if (isset($validated['notes']) && trim((string) $validated['notes']) === '') {
            $validated['notes'] = null;
        }

        if (isset($validated['middle_initial']) && $validated['middle_initial'] !== null) {
            $validated['middle_initial'] = strtoupper(trim(str_replace('.', '', $validated['middle_initial'])));
        }

        if (isset($validated['first_name'])) {
            $validated['first_name'] = ucwords(strtolower($validated['first_name']));
        }
        if (isset($validated['last_name'])) {
            $validated['last_name'] = ucwords(strtolower($validated['last_name']));
        }

        return $validated;
    }

    /**
     * Build the display name from the parts, or keep the supplied full name
     * when only one was given.
     *
     * @param  array<string, mixed>  $data
     */
    private function composeName(array $data, ?Member $member = null): string
    {
        $first = trim((string) ($data['first_name'] ?? ''));
        $last = trim((string) ($data['last_name'] ?? ''));
        $mi = trim((string) ($data['middle_initial'] ?? ''));

        if ($first === '' || $last === '') {
            return trim((string) ($member?->name ?? ''));
        }

        $parts = [$first];
        if ($mi !== '') {
            $parts[] = "{$mi}.";
        }
        $parts[] = $last;

        return implode(' ', $parts);
    }
}
