<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Turning a person on the list into a system user.
 *
 * The administrator never types a name, an address or a role: they pick a
 * member and supply the email the invitation goes to. Everything else is
 * copied, and the two records are linked so the same member can only ever
 * be picked once.
 */
class AdminMemberAccountCreationTest extends TestCase
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
            'email' => 'admin_'.uniqid().'@example.com',
            'role' => 'admin',
            'password' => 'password123',
        ]);
    }

    private function member(array $overrides = []): Member
    {
        $data = array_merge([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'name' => 'Juan Dela Cruz',
            'email' => 'juan.delacruz@example.com',
            'phone' => '09171234567',
            'barangay' => 'Malaybalay',
            'city' => 'Valencia',
            'province' => 'Bukidnon',
            'status' => Member::STATUS_ACTIVE,
        ], $overrides);

        return Member::create($data);
    }

    public function test_the_picker_lists_members_who_still_need_an_account(): void
    {
        $waiting = $this->member();
        $linked = $this->member([
            'first_name' => 'Maria', 'last_name' => 'Santos', 'name' => 'Maria Santos',
            'email' => 'maria@example.com', 'phone' => '09179876543',
        ]);
        $linked->update(['user_id' => User::create([
            'name' => 'Maria Santos', 'email' => 'maria@example.com',
            'role' => 'farmer', 'password' => 'password123',
        ])->id]);

        $this->actingAs($this->admin())
            ->get(route('admin.users.create', ['type' => 'farmer']))
            ->assertOk()
            ->assertSee($waiting->fullName())
            ->assertDontSee('Maria Santos');
    }

    public function test_inactive_members_are_not_offerable(): void
    {
        $inactive = $this->member(['status' => Member::STATUS_INACTIVE]);

        $this->actingAs($this->admin())
            ->get(route('admin.users.create', ['type' => 'farmer']))
            ->assertOk()
            ->assertDontSee($inactive->fullName());
    }

    public function test_picking_a_member_with_an_account_is_refused(): void
    {
        $linked = $this->member();
        $linked->update(['user_id' => User::create([
            'name' => 'Juan Dela Cruz', 'email' => 'juan.delacruz@example.com',
            'role' => 'farmer', 'password' => 'password123',
        ])->id]);

        $this->actingAs($this->admin())
            ->get(route('admin.users.create', ['member' => $linked->id]))
            ->assertRedirect(route('admin.users.create', ['type' => 'farmer']))
            ->assertSessionHas('error');
    }

    public function test_the_members_page_links_each_unlinked_member_to_account_creation(): void
    {
        $waiting = $this->member();

        $this->actingAs($this->admin())
            ->get(route('admin.members.index'))
            ->assertOk()
            ->assertSee(route('admin.users.create', ['member' => $waiting->id]), false)
            ->assertSee('Create Account');
    }

    public function test_creating_an_account_copies_the_member_record_and_links_it(): void
    {
        $member = $this->member([
            'assoc_member_no' => 'AIA-0042',
            'sex' => 'Male',
            'birth_date' => '1980-05-14',
            'civil_status' => 'Married',
            'farm_location' => 'Purok 3, Malaybalay',
            'land_ownership' => 'Owned',
            'years_farming' => 22,
        ]);

        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'member_id' => $member->id,
            'email' => 'juan.delacruz@example.com',
        ])->assertRedirect(route('admin.users.index'))->assertSessionHas('success');

        $user = User::where('email', 'juan.delacruz@example.com')->firstOrFail();

        $this->assertSame('farmer', $user->role);
        $this->assertSame(User::STATUS_PENDING, $user->status);
        $this->assertSame('Juan Dela Cruz', $user->name);
        $this->assertSame('+639171234567', $user->phone);
        $this->assertSame('Malaybalay', $user->barangay);
        $this->assertSame('AIA-0042', $user->assoc_member_no);
        $this->assertSame(22, (int) $user->years_farming);

        $this->assertSame($user->id, $member->fresh()->user_id);
    }

    public function test_the_posted_role_is_ignored_members_become_farmers(): void
    {
        $member = $this->member();

        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'member_id' => $member->id,
            'email' => 'juan.delacruz@example.com',
            'role' => 'store_owner',
        ])->assertRedirect();

        $this->assertSame(
            'farmer',
            User::where('email', 'juan.delacruz@example.com')->firstOrFail()->role
        );
    }

    public function test_a_member_cannot_be_turned_into_two_accounts(): void
    {
        $member = $this->member();

        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'member_id' => $member->id,
            'email' => 'first@example.com',
        ])->assertRedirect();

        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'member_id' => $member->id,
            'email' => 'second@example.com',
        ])->assertSessionHasErrors('member_id');

        $this->assertDatabaseMissing('users', ['email' => 'second@example.com']);
        $this->assertSame(1, User::where('role', 'farmer')->count());
    }

    public function test_a_member_without_an_email_can_be_given_one(): void
    {
        $member = $this->member(['email' => null]);
        $this->assertNull($member->email);

        $this->actingAs($this->admin())->get(route('admin.users.create', ['member' => $member->id]))
            ->assertOk()
            ->assertSee('Create Account for a Member');

        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'member_id' => $member->id,
            'email' => 'new.address@example.com',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'new.address@example.com',
            'role' => 'farmer',
        ]);
    }

    public function test_an_email_already_used_by_another_account_is_refused(): void
    {
        User::create([
            'name' => 'Someone Else', 'email' => 'taken@example.com',
            'role' => 'store_owner', 'password' => 'password123',
        ]);

        $member = $this->member();

        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'member_id' => $member->id,
            'email' => 'taken@example.com',
        ])->assertSessionHasErrors('email');

        $this->assertNull($member->fresh()->user_id, 'The member must stay available after a failed attempt.');
    }

    public function test_non_admins_cannot_reach_the_picker(): void
    {
        $farmer = User::create([
            'name' => 'Juan Dela Cruz', 'email' => 'farmer@example.com',
            'role' => 'farmer', 'password' => 'password123',
        ]);

        $this->actingAs($farmer)->get(route('admin.users.create'))->assertForbidden();
        $this->actingAs($farmer)->post(route('admin.users.store'), [])->assertForbidden();
    }
}
