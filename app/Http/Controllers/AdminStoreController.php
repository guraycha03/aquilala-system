<?php

namespace App\Http\Controllers;

use App\Exceptions\Onboarding\DuplicateAccountException;
use App\Exceptions\Onboarding\MailDeliveryException;
use App\Models\StoreDocument;
use App\Models\User;
use App\Services\UserInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Accredited supply stores.
 *
 * Store owners are outsiders: not association members, and never picked from
 * the members list. An administrator registers each store here — the farmer
 * accounts remain the only thing the members list is used for — and files
 * the store's legal papers one document at a time against it.
 */
class AdminStoreController extends Controller
{
    private const PRIVATE_DISK = 'local';

    /**
     * Every registered store with its paperwork count.
     */
    public function index(): View
    {
        $storeOwners = User::where('role', 'store_owner')
            ->withCount('products')
            ->withCount('documents')
            ->orderBy('store_name')
            ->orderBy('name')
            ->get();

        $totals = [
            'stores' => $storeOwners
                ->map(fn (User $u): string => trim((string) $u->store_name) !== '' ? $u->store_name : $u->name)
                ->unique()
                ->count(),
            'accounts' => $storeOwners->count(),
            'products' => (int) $storeOwners->sum('products_count'),
            'inactive' => $storeOwners->where('status', User::STATUS_DISABLED)->count(),
            'papers' => (int) $storeOwners->sum('documents_count'),
        ];

        return view('admin.stores.index', compact('storeOwners', 'totals'));
    }

    public function create(): View
    {
        return view('admin.stores.create');
    }

    /**
     * Register a store: the business details and the owner's sign-in account.
     *
     * No legal paper is collected here. The account goes out as pending with
     * an activation code, exactly like a farmer account, so the owner still
     * chooses their own password — the documents are filed afterwards from
     * the store's own page, one at a time.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateStore($request);

        if ($documentError = $this->validateApplicationDocumentPairs($request)) {
            return $documentError;
        }

        foreach ([
            'middle_initial', 'phone', 'address', 'barangay', 'city', 'province',
            'owner_address', 'business_type', 'ownership_type', 'business_description',
            'business_registration_number', 'tax_identification_number',
            'year_established', 'years_operating', 'operating_status',
            'operating_days', 'operating_hours',
        ] as $field) {
            if (trim((string) ($validated[$field] ?? '')) === '') {
                $validated[$field] = null;
            }
        }

        // The register form shows city and province fixed to the association's
        // home town — whatever was posted is replaced here. Editing (see
        // update()) keeps whatever is on the record.
        $validated['city'] = config('bulan.city');
        $validated['province'] = config('bulan.province');

        try {
            $delivery = [];
            $user = UserInvitationService::invite([
                'first_name' => $validated['first_name'],
                'middle_initial' => $validated['middle_initial'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'role' => 'store_owner',
                'barangay' => $validated['barangay'],
                'city' => $validated['city'],
                'province' => $validated['province'],
            ], $delivery);
        } catch (DuplicateAccountException $e) {
            return $this->storeFailure($request, $e->getMessage(), $e->field);
        } catch (MailDeliveryException $e) {
            return $this->storeFailure($request, $e->getMessage(), 'email');
        }

        $user->forceFill([
            'store_name' => $validated['store_name'],
            'sex' => $validated['sex'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'civil_status' => $validated['civil_status'] ?? null,
            'business_type' => $validated['business_type'] ?? null,
            'ownership_type' => $validated['ownership_type'] ?? null,
            'business_description' => $validated['business_description'] ?? null,
            'business_registration_number' => $validated['business_registration_number'] ?? null,
            'tax_identification_number' => $validated['tax_identification_number'] ?? null,
            'year_established' => $validated['year_established'] ?? null,
            'years_operating' => $validated['years_operating'] ?? null,
            'operating_status' => $validated['operating_status'] ?? null,
            'operating_days' => $validated['operating_days'] ?? null,
            'operating_hours' => $validated['operating_hours'] ?? null,
            'address' => $validated['address'],
            'contact_number' => $validated['phone'],
            'owner_address' => $validated['owner_address'] ?? null,
        ])->save();

        $this->fileApplicationDocuments($request, $user);

        $message = $delivery && empty($delivery['sent'])
            ? "{$user->store_name} was registered, but the activation email could not be sent — {$delivery['error']} Use Resend Invitation on the Users list once a sender domain is configured."
            : "{$user->store_name} is registered. The owner can now sign in with {$user->email}, enter the 6-digit activation code we emailed them, and set their own password. Any remaining legal papers can be filed from the Stores list.";

        return redirect()
            ->route('admin.stores.index')
            ->with('success', $message);
    }

    public function edit(User $store): View
    {
        abort_unless($store->role === 'store_owner', 404);

        $store->load('documents');

        return view('admin.stores.edit', [
            'store' => $store,
            'types' => StoreDocument::TYPES,
        ]);
    }

    public function update(Request $request, User $store): RedirectResponse
    {
        abort_unless($store->role === 'store_owner', 404);

        $validated = $this->validateStore($request, $store);

        // Blank optional fields go in as NULL, not as empty strings, so the
        // unique phone index can hold more than one store without a number.
        foreach ([
            'middle_initial', 'phone', 'address', 'barangay', 'city', 'province',
            'owner_address', 'business_type', 'ownership_type', 'business_description',
            'business_registration_number', 'tax_identification_number',
            'year_established', 'years_operating', 'operating_status',
            'operating_days', 'operating_hours',
        ] as $field) {
            if (trim((string) ($validated[$field] ?? '')) === '') {
                $validated[$field] = null;
            }
        }

        $store->forceFill([
            'store_name' => $validated['store_name'],
            'sex' => $validated['sex'] ?? $store->sex,
            'birth_date' => $validated['birth_date'] ?? $store->birth_date,
            'civil_status' => $validated['civil_status'] ?? $store->civil_status,
            'business_type' => $validated['business_type'] ?? null,
            'ownership_type' => $validated['ownership_type'] ?? null,
            'business_description' => $validated['business_description'] ?? null,
            'business_registration_number' => $validated['business_registration_number'] ?? null,
            'tax_identification_number' => $validated['tax_identification_number'] ?? null,
            'year_established' => $validated['year_established'] ?? null,
            'years_operating' => $validated['years_operating'] ?? null,
            'operating_status' => $validated['operating_status'] ?? null,
            'operating_days' => $validated['operating_days'] ?? null,
            'operating_hours' => $validated['operating_hours'] ?? null,
            'name' => UserInvitationService::buildFullName($validated),
            'first_name' => trim($validated['first_name']),
            'middle_initial' => $validated['middle_initial'] !== null
                ? strtoupper(trim($validated['middle_initial']))
                : null,
            'last_name' => trim($validated['last_name']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] !== null
                ? UserInvitationService::normalizePhone($validated['phone'])
                : null,
            'contact_number' => $validated['phone'],
            'address' => $validated['address'],
            'barangay' => $validated['barangay'],
            'city' => $validated['city'],
            'province' => $validated['province'],
            'owner_address' => $validated['owner_address'] ?? null,
        ])->save();

        return redirect()
            ->route('admin.stores.index')
            ->with('success', "{$store->store_name} was updated.");
    }

    public function destroy(User $store): RedirectResponse
    {
        abort_unless($store->role === 'store_owner', 404);

        $name = $store->store_name ?: $store->name;

        DB::transaction(function () use ($store) {
            foreach ($store->documents as $document) {
                Storage::disk(self::PRIVATE_DISK)->delete($document->path);
            }

            $store->delete();
        });

        return redirect()
            ->route('admin.stores.index')
            ->with('success', "{$name} and its filed paperwork were removed.");
    }

    /**
     * File one legal paper: the type is chosen from the dropdown and exactly
     * one file is uploaded with it. Papers are added one at a time, never in
     * a fixed set of slots.
     */
    public function storeDocument(Request $request, User $store): RedirectResponse
    {
        abort_unless($store->role === 'store_owner', 404);

        $validated = $request->validate([
            'document_type' => ['required', 'string', Rule::in(StoreDocument::TYPES)],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'document_type.required' => 'Choose the type of paper you are filing.',
            'document_type.in' => 'Choose a document type from the list.',
            'file.required' => 'Attach the file for this paper.',
            'file.mimes' => 'Upload a PDF, JPG or PNG file.',
            'file.max' => 'That file is larger than 10 MB.',
        ]);

        $file = $request->file('file');

        $directory = 'store-documents/'.$store->id;
        $stem = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = Str::random(10).($stem !== '' ? '_'.Str::limit($stem, 60, '') : '')
            .'.'.$file->getClientOriginalExtension();

        $path = $file->storeAs($directory, $filename, self::PRIVATE_DISK);

        if ($path === false) {
            return back()->with('error', 'The file could not be stored. Please try again.');
        }

        StoreDocument::create([
            'user_id' => $store->id,
            'document_type' => $validated['document_type'],
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'uploaded_by' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', "{$validated['document_type']} was filed for {$store->store_name}.");
    }

    public function destroyDocument(User $store, StoreDocument $document): RedirectResponse
    {
        abort_unless($store->role === 'store_owner', 404);
        abort_unless((int) $document->user_id === (int) $store->id, 404);

        $type = $document->document_type;

        Storage::disk(self::PRIVATE_DISK)->delete($document->path);
        $document->delete();

        return back()->with('success', "The {$type} was removed from {$store->store_name}'s file.");
    }

    public function downloadDocument(User $store, StoreDocument $document)
    {
        abort_unless($store->role === 'store_owner', 404);
        abort_unless((int) $document->user_id === (int) $store->id, 404);

        abort_unless(Storage::disk(self::PRIVATE_DISK)->exists($document->path), 404);

        return Storage::disk(self::PRIVATE_DISK)->download($document->path, $document->original_name);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStore(Request $request, ?User $existing = null): array
    {
        $emailRule = app()->environment('production') ? 'email:rfc,dns,spoof' : 'email:rfc';

        return $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
            'middle_initial' => ['nullable', 'string', 'max:50', 'regex:/^[\pL\s\.]+$/u'],
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
            'email' => [
                'required', 'string', 'max:254', $emailRule,
                Rule::unique('users', 'email')->ignore($existing?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            // Registering: the dropdown only offers Bulan's barangays (see
            // config/bulan.php) — hold the same line server-side. Editing
            // keeps legacy addresses working.
            'barangay' => $existing === null
                ? ['nullable', 'string', 'max:100', Rule::in(config('bulan.barangays'))]
                : ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'sex' => ['nullable', 'string', Rule::in(['Male', 'Female', 'Prefer not to say'])],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'civil_status' => ['nullable', 'string', Rule::in(['Single', 'Married', 'Widowed', 'Separated', 'Prefer not to say'])],
            'owner_address' => ['nullable', 'string', 'max:500'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'ownership_type' => ['nullable', 'string', Rule::in(['Sole proprietorship', 'Partnership', 'Corporation', 'Cooperative', 'Other'])],
            'business_description' => ['nullable', 'string', 'max:2000'],
            'business_registration_number' => ['nullable', 'string', 'max:100'],
            'tax_identification_number' => ['nullable', 'string', 'max:50'],
            'year_established' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
            'years_operating' => ['nullable', 'integer', 'min:0', 'max:200'],
            'operating_status' => ['nullable', Rule::in(['Currently operating', 'Seasonal', 'Temporarily closed'])],
            'operating_days' => ['nullable', 'string', 'max:150'],
            'operating_hours' => ['nullable', 'string', 'max:100'],
            'legal_document_types' => ['nullable', 'array', 'max:10'],
            'legal_document_types.*' => ['nullable', 'string', Rule::in(StoreDocument::TYPES)],
            'legal_files' => ['nullable', 'array', 'max:10'],
            'legal_files.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'store_name.required' => 'Enter the store or business name.',
            'first_name.required' => "Enter the owner's first name.",
            'first_name.regex' => 'First name may only contain letters, spaces, and hyphens.',
            'last_name.required' => "Enter the owner's last name.",
            'last_name.regex' => 'Last name may only contain letters, spaces, and hyphens.',
            'email.required' => 'An email address is required — it is how the owner signs in.',
            'email.unique' => 'That email address is already in use by another account.',
            'email.email' => 'Enter a valid email address.',
            'barangay.in' => 'Choose a barangay of Bulan, Sorsogon from the list.',
            'ownership_type.in' => 'Choose a valid business ownership type.',
            'operating_status.in' => 'Choose a valid operating status.',
        ]);
    }

    /**
     * Save the legal papers supplied with the offline application.
     *
     * The arrays are parallel: each selected document type labels the file
     * in the same position. Additional papers can still be added later.
     */
    private function fileApplicationDocuments(Request $request, User $store): void
    {
        $types = $request->input('legal_document_types', []);
        $files = $request->file('legal_files', []);

        if (! is_array($types) || ! is_array($files)) {
            return;
        }

        foreach ($types as $index => $type) {
            $file = $files[$index] ?? null;

            if (trim((string) $type) === '' && $file === null) {
                continue;
            }

            if (trim((string) $type) === '' || $file === null) {
                continue;
            }

            $directory = 'store-documents/'.$store->id;
            $stem = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $filename = Str::random(10).($stem !== '' ? '_'.Str::limit($stem, 60, '') : '')
                .'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs($directory, $filename, self::PRIVATE_DISK);

            if ($path === false) {
                continue;
            }

            StoreDocument::create([
                'user_id' => $store->id,
                'document_type' => $type,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }

    private function validateApplicationDocumentPairs(Request $request): ?RedirectResponse
    {
        $types = $request->input('legal_document_types', []);
        $files = $request->file('legal_files', []);

        if (! is_array($types) || ! is_array($files)) {
            return null;
        }

        foreach (array_unique(array_merge(array_keys($types), array_keys($files))) as $index) {
            $hasType = trim((string) ($types[$index] ?? '')) !== '';
            $hasFile = isset($files[$index]) && $files[$index] !== null;

            if ($hasType !== $hasFile) {
                return back()
                    ->withErrors(['legal_files' => 'Each legal paper must have both a document type and an attached file.'])
                    ->withInput();
            }
        }

        return null;
    }

    private function storeFailure(Request $request, string $message, string $field): RedirectResponse
    {
        return back()->withErrors([$field => $message])->withInput();
    }
}
