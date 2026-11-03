<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\StoreDocument;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Admin-only store creation and the paperwork file that goes with it.
 *
 * Stores are outsiders: registering one must never touch the members
 * list, and every legal paper is filed one document at a time against a type
 * chosen from the dropdown rather than a fixed set of slots.
 */
class AdminStoreTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        Mail::fake();
        Storage::fake('local');
    }

    private function admin(): User
    {
        return $this->admin ??= User::create([
            'name' => 'System Admin',
            'first_name' => 'System',
            'last_name' => 'Admin',
            'email' => 'admin_'.uniqid().'@example.com',
            'role' => 'admin',
            'password' => 'password123',
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'store_name' => 'Aquilala Agri Depot',
            'first_name' => 'Sam',
            'last_name' => 'Store',
            'email' => 'store_'.uniqid().'@example.com',
            // Unique by default: `phone` carries a unique index, so two stores
            // registered in one test must not share a number.
            'phone' => '09'.str_pad((string) random_int(0, 99999999), 9, '0', STR_PAD_LEFT),
            'address' => 'National Highway',
            'barangay' => 'San Vicente',
            // Posted as anything — registration anchors city and province
            // to the association's home town (see config/bulan.php).
            'city' => 'Valencia',
            'province' => 'Bukidnon',
        ], $overrides);
    }

    private function makeStore(array $overrides = []): User
    {
        $this->actingAs($this->admin())
            ->post(route('admin.stores.store'), $this->payload($overrides))
            ->assertRedirect(route('admin.stores.index'));

        return User::where('role', 'store_owner')->latest('id')->firstOrFail();
    }

    private function paper(User $store, array $overrides = []): StoreDocument
    {
        $file = UploadedFile::fake()->createWithContent('permit.pdf', '%PDF-1.4 fake permit');

        $response = $this->actingAs($this->admin())->post(
            route('admin.stores.documents.store', $store),
            array_merge([
                'document_type' => StoreDocument::TYPES[0],
                'file' => $file,
            ], $overrides)
        );
        $response->assertSessionHasNoErrors();

        return StoreDocument::where('user_id', $store->id)->latest('id')->firstOrFail();
    }

    // ── Registration ─────────────────────────────────────────────

    public function test_registering_a_store_creates_a_pending_store_owner_account(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.stores.store'), $this->payload(['phone' => '09171234567']))
            ->assertRedirect(route('admin.stores.index'))
            ->assertSessionHas('success');

        $user = User::where('role', 'store_owner')->firstOrFail();

        $this->assertSame('Aquilala Agri Depot', $user->store_name);
        $this->assertSame('Sam Store', $user->name);
        $this->assertSame(User::STATUS_PENDING, $user->status);
        $this->assertSame('National Highway', $user->address);
        // `phone` is the normalized sign-in identity, `contact_number` the
        // number exactly as the administrator typed it.
        $this->assertSame('+639171234567', $user->phone);
        $this->assertSame('09171234567', $user->contact_number);
        $this->assertSame('San Vicente', $user->barangay);
        $this->assertSame('Bulan', $user->city);
        $this->assertSame('Sorsogon', $user->province);
    }

    public function test_registering_a_store_never_creates_or_touches_a_member(): void
    {
        Member::create([
            'first_name' => 'Juan', 'last_name' => 'Dela Cruz',
            'name' => 'Juan Dela Cruz', 'status' => Member::STATUS_ACTIVE,
        ]);

        $store = $this->makeStore();

        // The members list is untouched and the store owner has no member
        // record hanging off their account.
        $this->assertDatabaseCount('members', 1);
        $this->assertNull($store->member);
        $this->assertNull(Member::first()->user_id);
    }

    public function test_the_store_list_links_to_the_creation_form(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.stores.index'))
            ->assertOk()
            ->assertSee(route('admin.stores.create'), false)
            ->assertDontSee('Legal Papers on File')
            ->assertDontSee('Store Owner Accounts');

        // The creation form itself must render, and it is a plain business
        // form — there is no member picker anywhere in this flow.
        $this->actingAs($this->admin())
            ->get(route('admin.stores.create'))
            ->assertOk()
            ->assertSee('Register a Store')
            ->assertSee('name="store_name"', false)
            ->assertSee('name="first_name"', false)
            ->assertSee('id="operatingDaysFrom"', false)
            ->assertSee('id="operatingDaysTo"', false)
            ->assertSee('id="operatingHoursFrom"', false)
            ->assertSee('id="operatingHoursTo"', false)
            ->assertSee('placeholder="0000 000 00"', false)
            ->assertDontSee('Choose a Member');
    }

    public function test_the_edit_page_lists_every_document_type_in_the_dropdown(): void
    {
        $store = $this->makeStore();

        $response = $this->actingAs($this->admin())->get(route('admin.stores.edit', $store));

        $response->assertOk();

        foreach (StoreDocument::TYPES as $type) {
            $response->assertSee('<option value="'.e($type).'"', false);
        }

        $response->assertSee('name="document_type"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('id="docForm"', false);
    }

    public function test_a_duplicate_email_is_refused_and_no_account_is_left_behind(): void
    {
        User::create([
            'name' => 'Someone Else', 'email' => 'taken@example.com',
            'role' => 'store_owner', 'password' => 'password123',
        ]);

        // Instantiate the administrator first so they are not counted as a
        // stray account created by the request under test.
        $this->admin();
        $before = User::count();

        $this->actingAs($this->admin())
            ->post(route('admin.stores.store'), $this->payload(['email' => 'taken@example.com']))
            ->assertSessionHasErrors('email');

        $this->assertSame($before, User::count());
    }

    // ── Paperwork ────────────────────────────────────────────────

    public function test_filing_a_paper_stores_exactly_one_document_of_the_chosen_type(): void
    {
        $store = $this->makeStore();

        $document = $this->paper($store);

        $this->assertSame(StoreDocument::TYPES[0], $document->document_type);
        $this->assertSame('permit.pdf', $document->original_name);
        $this->assertSame($this->admin()->id, $document->uploaded_by);
        Storage::disk('local')->assertExists($document->path);

        $this->assertSame(1, $store->documents()->count());
    }

    public function test_a_type_outside_the_dropdown_is_refused(): void
    {
        $store = $this->makeStore();

        $this->actingAs($this->admin())
            ->post(route('admin.stores.documents.store', $store), [
                'document_type' => 'Made Up Paper',
                'file' => UploadedFile::fake()->createWithContent('x.pdf', '%PDF-1.4'),
            ])
            ->assertSessionHasErrors('document_type');

        $this->assertSame(0, $store->documents()->count());
    }

    public function test_a_papers_file_must_be_attached(): void
    {
        $store = $this->makeStore();

        $this->actingAs($this->admin())
            ->post(route('admin.stores.documents.store', $store), [
                'document_type' => StoreDocument::TYPES[0],
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, $store->documents()->count());
    }

    public function test_a_paper_cannot_be_filed_against_a_different_store(): void
    {
        $first = $this->makeStore(['store_name' => 'First Store', 'email' => 'first@example.com']);
        $second = $this->makeStore(['store_name' => 'Second Store', 'email' => 'second@example.com']);

        $document = $this->paper($first);

        $this->actingAs($this->admin())
            ->delete(route('admin.stores.documents.destroy', [$second, $document]))
            ->assertNotFound();

        $this->assertDatabaseHas('store_documents', ['id' => $document->id]);
    }

    public function test_removing_a_paper_deletes_the_file_from_disk(): void
    {
        $store = $this->makeStore();
        $document = $this->paper($store);
        $path = $document->path;

        $this->actingAs($this->admin())
            ->delete(route('admin.stores.documents.destroy', [$store, $document]))
            ->assertRedirect();

        $this->assertDatabaseMissing('store_documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_a_paper_can_be_downloaded_under_its_original_name(): void
    {
        $store = $this->makeStore();
        $document = $this->paper($store);

        $this->actingAs($this->admin())
            ->get(route('admin.stores.documents.download', [$store, $document]))
            ->assertOk()
            ->assertDownload('permit.pdf');
    }

    public function test_deleting_a_store_removes_its_account_its_paperwork_and_its_files(): void
    {
        $store = $this->makeStore();
        $document = $this->paper($store);
        $path = $document->path;

        $this->actingAs($this->admin())
            ->delete(route('admin.stores.destroy', $store))
            ->assertRedirect(route('admin.stores.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $store->id]);
        $this->assertSame(0, StoreDocument::count());
        Storage::disk('local')->assertMissing($path);
    }

    // ── Access ───────────────────────────────────────────────────

    public function test_non_admins_cannot_reach_any_store_route(): void
    {
        $farmer = User::create([
            'name' => 'Juan Dela Cruz', 'email' => 'farmer@example.com',
            'role' => 'farmer', 'password' => 'password123',
        ]);
        $store = $this->makeStore();
        $document = $this->paper($store);

        $this->actingAs($farmer)->get(route('admin.stores.index'))->assertForbidden();
        $this->actingAs($farmer)->get(route('admin.stores.create'))->assertForbidden();
        $this->actingAs($farmer)->post(route('admin.stores.store'), $this->payload())->assertForbidden();
        $this->actingAs($farmer)->get(route('admin.stores.edit', $store))->assertForbidden();
        $this->actingAs($farmer)->post(
            route('admin.stores.documents.store', $store),
            ['document_type' => StoreDocument::TYPES[0]]
        )->assertForbidden();
        $this->actingAs($farmer)->delete(route('admin.stores.destroy', $store))->assertForbidden();
    }

    public function test_a_farmer_account_cannot_be_managed_as_a_store(): void
    {
        $farmer = User::create([
            'name' => 'Juan Dela Cruz', 'email' => 'farmer@example.com',
            'role' => 'farmer', 'password' => 'password123',
        ]);

        $this->actingAs($this->admin())->get(route('admin.stores.edit', $farmer))->assertNotFound();
        $this->actingAs($this->admin())->delete(route('admin.stores.destroy', $farmer))->assertNotFound();
    }
}
