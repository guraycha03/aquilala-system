<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A member's own picture, and a farmer's name a store may not read.
 *
 * The picture is theirs: it is uploaded once, it follows the account on
 * every device, and removing it puts the account back on its initials.
 *
 * The name is the other way round. A store owner sees that a farmer's
 * estimate exists, never who made it: a farmer must not feel watched for
 * costing out, and a store must not sit on a false hope of a sale.
 */
class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $name, string $role): User
    {
        return User::create([
            'name' => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? 'Test',
            'email' => strtolower(str_replace(' ', '.', $name)).'_'.uniqid().'@example.test',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
            'password' => 'password123',
        ]);
    }

    // ────────────────────────────────────────────────────────────
    //  The picture
    // ────────────────────────────────────────────────────────────

    public function test_a_member_updates_their_own_address(): void
    {
        $farmer = $this->makeUser('Ana Farmer', 'farmer');

        $this->actingAs($farmer)
            ->patch(route('profile.address.update'), [
                'address'  => 'Purok 3, Brgy. Aquino',
                'barangay' => 'Aquino',
                'city'     => 'Bulan',
                'province' => 'Sorsogon',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $farmer->refresh();
        $this->assertSame('Purok 3, Brgy. Aquino', $farmer->address);
        $this->assertSame('Aquino', $farmer->barangay);
        $this->assertSame('Bulan', $farmer->city);
        $this->assertSame('Sorsogon', $farmer->province);
    }

    public function test_a_member_cannot_change_anything_but_the_address(): void
    {
        $farmer = $this->makeUser('Ana Farmer', 'farmer');

        // The rest of the profile is the association's record: the
        // address route must not carry a way to change any of it.
        $this->actingAs($farmer)
            ->patch(route('profile.address.update'), [
                'address' => 'Purok 3',
                'name'    => 'Someone Else',
                'role'    => 'admin',
                'status'  => 'active',
                'phone'   => '+639000000000',
            ])
            ->assertRedirect();

        $farmer->refresh();
        $this->assertSame('Ana Farmer', $farmer->name);
        $this->assertSame('farmer', $farmer->role);
        $this->assertSame('Purok 3', $farmer->address);
    }

    public function test_the_association_address_is_corrected_from_the_admin_side(): void
    {
        $admin = $this->makeUser('Chair Person', 'admin');

        $this->actingAs($admin)
            ->patch(route('profile.address.update'), ['city' => 'Bulan'])
            ->assertForbidden();
    }

    public function test_guests_cannot_change_an_address(): void
    {
        $this->patch(route('profile.address.update'), ['city' => 'Bulan'])
            ->assertRedirect(route('login'));
    }

    public function test_the_picture_is_changed_from_the_avatar_itself_and_not_from_a_card(): void
    {
        $farmer = $this->makeUser('Ana Farmer', 'farmer');

        $html = $this->actingAs($farmer)
            ->get(route('profile.index'))
            ->assertOk()
            ->getContent();

        // The picture at the top IS the control: tapping it offers what
        // can be done with it.
        $this->assertStringContainsString('id="profileAvatarBtn"', $html);
        $this->assertStringContainsString('id="profilePhotoMenu"', $html);
        $this->assertStringContainsString('id="profilePhotoUpload"', $html);
        $this->assertStringContainsString('id="profilePhotoRemove"', $html);
        $this->assertStringContainsString('id="profilePhotoView"', $html);

        // No second card for the picture.
        $this->assertStringNotContainsString('Profile picture</div>', $html);
    }

    public function test_a_member_uploads_a_picture_and_it_is_kept_on_the_account(): void
    {
        Storage::fake('public');
        $farmer = $this->makeUser('Ana Farmer', 'farmer');

        $response = $this->actingAs($farmer)->postJson(route('profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('me.png', 120, 120),
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $farmer->refresh();
        $this->assertNotNull($farmer->profile_photo);
        Storage::disk('public')->assertExists($farmer->profile_photo);
        $this->assertStringContainsString($farmer->profile_photo, (string) $response->json('url'));
    }

    public function test_removing_the_picture_puts_the_account_back_on_its_initials(): void
    {
        Storage::fake('public');
        $farmer = $this->makeUser('Ana Farmer', 'farmer');

        $this->actingAs($farmer)->postJson(route('profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('me.png', 120, 120),
        ])->assertOk();

        $path = $farmer->fresh()->profile_photo;

        $response = $this->actingAs($farmer->fresh())->deleteJson(route('profile.photo.remove'));

        $response->assertOk()->assertJson(['ok' => true, 'url' => null]);
        $this->assertNull($farmer->fresh()->profile_photo);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame('AF', $farmer->fresh()->initials());
    }

    public function test_replacing_the_picture_removes_the_old_file(): void
    {
        Storage::fake('public');
        $farmer = $this->makeUser('Ana Farmer', 'farmer');

        $this->actingAs($farmer)->postJson(route('profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('first.png', 100, 100),
        ])->assertOk();
        $first = $farmer->fresh()->profile_photo;

        $this->actingAs($farmer->fresh())->postJson(route('profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('second.png', 100, 100),
        ])->assertOk();

        $second = $farmer->fresh()->profile_photo;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_a_file_that_is_not_a_picture_is_refused(): void
    {
        Storage::fake('public');
        $farmer = $this->makeUser('Ana Farmer', 'farmer');

        $this->actingAs($farmer)->postJson(route('profile.photo.update'), [
            'photo' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ])->assertStatus(422);

        $this->assertNull($farmer->fresh()->profile_photo);
    }

    public function test_guests_cannot_upload_a_picture(): void
    {
        Storage::fake('public');

        $this->postJson(route('profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('guest.png', 50, 50),
        ])->assertStatus(401);
    }

    public function test_the_picture_shows_on_the_profile_page_and_in_the_side_menu(): void
    {
        Storage::fake('public');
        $farmer = $this->makeUser('Ana Farmer', 'farmer');

        $this->actingAs($farmer)->postJson(route('profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('me.png', 120, 120),
        ])->assertOk();

        $url = $farmer->fresh()->profilePhotoUrl();

        $this->actingAs($farmer->fresh())->get(route('profile.index'))
            ->assertOk()
            ->assertSee($url, false);

        // The side menu carries the same picture on every page.
        $this->actingAs($farmer->fresh())->get(route('profile.index'))
            ->assertOk()
            ->assertSee($url, false);
        $this->actingAs($farmer->fresh())->get(route('farmer.quotations.index'))
            ->assertOk()
            ->assertSee($url, false);
    }

    // ────────────────────────────────────────────────────────────
    //  The name a store may not read
    // ────────────────────────────────────────────────────────────

    public function test_a_name_is_masked_for_a_store(): void
    {
        $farmer = $this->makeUser('Juan Cruz', 'farmer');

        $this->assertSame('Jxxx Cxxx', $farmer->maskedName());
        $this->assertSame('JC', $farmer->initials());
    }

    public function test_a_store_never_sees_the_farmer_full_name(): void
    {
        $store = $this->makeUser('Oakridge Store', 'store_owner');
        $farmer = $this->makeUser('Juan Cruz', 'farmer');

        $product = Product::create([
            'user_id'           => $store->id,
            'sku'               => 'SKU-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'name'              => 'Premium Urea 46-0-0',
            'category'          => 'Fertilizers',
            'price'             => 1450.00,
            'stock_quantity'    => 40,
            'reorder_threshold' => 5,
            'status'            => 'active',
        ]);

        $quotation = Quotation::create([
            'user_id'              => $farmer->id,
            'quotation_number'     => 'QT-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'crop_type'            => 'Rice',
            'season'               => 'wet',
            'land_area'            => 1.0,
            'estimated_total_cost' => 300.00,
            'status'               => Quotation::STATUS_COMPUTED,
            'valid_until'          => now()->addDays(14),
        ]);

        $quotation->items()->create([
            'product_id'          => $product->id,
            'product_name'        => $product->name,
            'price_at_generation' => 1450.00,
            'calculated_quantity' => 3,
            'subtotal'            => 4350.00,
        ]);

        // The store's list: the name is masked, and the full name is not
        // anywhere in the page - not in the row, not in the search index.
        $list = $this->actingAs($store)->get(route('store.quotations.index'))->assertOk();
        $list->assertSee('Jxxx Cxxx', false);
        $list->assertDontSee('Juan Cruz', false);

        // The store's detail page: masked as well.
        $detail = $this->actingAs($store)->get(route('store.quotations.show', $quotation))->assertOk();
        $detail->assertSee('Jxxx Cxxx', false);
        $detail->assertDontSee('Juan Cruz', false);
    }

    public function test_a_farmer_still_sees_their_own_full_name(): void
    {
        $farmer = $this->makeUser('Juan Cruz', 'farmer');

        $this->actingAs($farmer)->get(route('farmer.quotations.index'))
            ->assertOk()
            ->assertSee('Juan Cruz', false);
    }
}
