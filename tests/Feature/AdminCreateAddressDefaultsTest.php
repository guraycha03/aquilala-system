<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The address block on both admin-side create forms is anchored to the
 * association's home town: Bulan, Sorsogon arrives already filled in and
 * read-only, and the barangay is a dropdown of Bulan's 63 barangays
 * (PSGC 0506203000) — the only address part anyone picks.
 *
 * The server keeps the same promise: city and province are overwritten on
 * create, and a barangay outside Bulan is refused. Editing an existing
 * record stays free-text, so legacy addresses still open and save.
 */
class AdminCreateAddressDefaultsTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        Mail::fake();
    }

    private function admin(): User
    {
        return $this->admin ??= User::create([
            'name' => 'System Admin',
            'first_name' => 'System',
            'last_name' => 'Admin',
            'email' => 'address_admin_'.uniqid().'@example.com',
            'role' => 'admin',
            'password' => 'password123',
        ]);
    }

    /**
     * The option values inside the form's barangay <select>, in order
     * (the leading blank placeholder is dropped).
     *
     * @return array<int, string>
     */
    private function barangayOptions(string $html): array
    {
        preg_match('/<select name="barangay">.*?<\/select>/s', $html, $block);
        preg_match_all('/<option value="([^"]*)"/', $block[0] ?? '', $matches);

        return array_values(array_filter($matches[1], fn ($value) => $value !== ''));
    }

    // ── The researched list ───────────────────────────────────────

    #[Test]
    public function test_the_bulan_list_is_the_psgc_set_of_sixty_three(): void
    {
        $this->assertSame('Bulan', config('bulan.city'));
        $this->assertSame('Sorsogon', config('bulan.province'));

        $barangays = config('bulan.barangays');

        $this->assertCount(63, $barangays, 'PSGC 0506203000 carries 63 barangays: 55 + 8 Poblacion zones.');
        $this->assertSame($barangays, array_values(array_unique($barangays)), 'no duplicates');
        $this->assertSame('A. Bonifacio', $barangays[0]);
        $this->assertSame('Zone VIII Pob.', end($barangays));
        $this->assertContains('San Vicente', $barangays);
        $this->assertContains('Zone IV Pob.', $barangays);
        $this->assertContains('Osmeña', $barangays, 'accented names are kept as officially written');
    }

    // ── The two create forms ──────────────────────────────────────

    #[Test]
    public function test_the_member_create_form_offers_only_bulan_barangays(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.members.create'));

        $response->assertOk();

        $this->assertSame(
            config('bulan.barangays'),
            $this->barangayOptions($response->getContent()),
            'The barangay dropdown must list exactly the Bulan, Sorsogon set.'
        );

        $response
            ->assertSee('<input type="text" name="city" value="Bulan" readonly maxlength="100">', false)
            ->assertSee('<input type="text" name="province" value="Sorsogon" readonly maxlength="100">', false);
    }

    #[Test]
    public function test_the_store_create_form_offers_only_bulan_barangays(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.stores.create'));

        $response->assertOk();

        $this->assertSame(
            config('bulan.barangays'),
            $this->barangayOptions($response->getContent()),
            'The barangay dropdown must list exactly the Bulan, Sorsogon set.'
        );

        $response
            ->assertSee('<input type="text" name="city" value="Bulan" readonly maxlength="100">', false)
            ->assertSee('<input type="text" name="province" value="Sorsogon" readonly maxlength="100">', false);
    }

    // ── The server holds the same line ────────────────────────────

    #[Test]
    public function test_member_creation_anchors_city_and_province_to_bulan(): void
    {
        $this->actingAs($this->admin())->post(route('admin.members.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'barangay' => 'San Vicente',
            'city' => 'Valencia',
            'province' => 'Bukidnon',
            'land_area' => '2.5',
            'land_area_unit' => 'ha',
        ])
            ->assertRedirect(route('admin.members.index'))
            ->assertSessionHas('success');

        $member = Member::where('last_name', 'Santos')->firstOrFail();

        $this->assertSame('San Vicente', $member->barangay);
        $this->assertSame('Bulan', $member->city);
        $this->assertSame('Sorsogon', $member->province);
    }

    #[Test]
    public function test_a_barangay_outside_bulan_is_refused_on_member_creation(): void
    {
        $this->actingAs($this->admin())->post(route('admin.members.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'barangay' => 'Malaybalay',
        ])->assertSessionHasErrors([
            'barangay' => 'Choose a barangay of Bulan, Sorsogon from the list.',
        ]);

        $this->assertDatabaseMissing('members', ['last_name' => 'Santos']);
    }

    #[Test]
    public function test_a_barangay_outside_bulan_is_refused_on_store_registration(): void
    {
        $this->actingAs($this->admin())->post(route('admin.stores.store'), [
            'store_name' => 'Aquilala Agri Depot',
            'first_name' => 'Sam',
            'last_name' => 'Store',
            'email' => 'outside_brgy_'.uniqid().'@example.com',
            'phone' => '09171234567',
            'barangay' => 'Malaybalay',
            'city' => 'Valencia',
            'province' => 'Bukidnon',
        ])->assertSessionHasErrors([
            'barangay' => 'Choose a barangay of Bulan, Sorsogon from the list.',
        ]);

        $this->assertSame(0, User::where('role', 'store_owner')->count());
    }
}
