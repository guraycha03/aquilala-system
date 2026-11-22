<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminArchiveSelectionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'System Admin',
            'email' => 'admin_' . uniqid() . '@example.com',
            'role' => 'admin',
            'password' => 'password123',
        ]);
    }

    private function member(array $overrides = []): Member
    {
        return Member::create(array_merge([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'name' => 'Juan Dela Cruz',
            'email' => 'juan_' . uniqid() . '@example.com',
            'status' => Member::STATUS_ACTIVE,
        ], $overrides));
    }

    public function test_selected_users_are_archived_without_permanent_deletion(): void
    {
        $admin = $this->admin();
        $first = User::factory()->create(['role' => 'farmer']);
        $second = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($admin)
            ->post(route('admin.users.archive'), ['ids' => "{$first->id},{$second->id}"])
            ->assertRedirect();

        $this->assertNotNull($first->fresh()->archived_at);
        $this->assertNotNull($second->fresh()->archived_at);
        $this->assertDatabaseHas('users', ['id' => $first->id]);
        $this->assertDatabaseHas('users', ['id' => $second->id]);
    }

    public function test_current_admin_cannot_archive_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.archive'), ['ids' => (string) $admin->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull($admin->fresh()->archived_at);
    }

    public function test_selected_members_are_archived_without_permanent_deletion(): void
    {
        $admin = $this->admin();
        $first = $this->member();
        $second = $this->member(['first_name' => 'Maria', 'name' => 'Maria Dela Cruz']);

        $this->actingAs($admin)
            ->post(route('admin.members.archive'), ['ids' => "{$first->id},{$second->id}"])
            ->assertRedirect();

        $this->assertNotNull($first->fresh()->archived_at);
        $this->assertNotNull($second->fresh()->archived_at);
        $this->assertDatabaseHas('members', ['id' => $first->id]);
        $this->assertDatabaseHas('members', ['id' => $second->id]);
    }

    public function test_archived_users_and_members_are_hidden_from_normal_indexes(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => 'Archived Account', 'role' => 'farmer']);
        $member = $this->member(['name' => 'Archived Member']);

        $user->delete();
        $member->delete();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee('Archived Account');

        $this->actingAs($admin)
            ->get(route('admin.members.index'))
            ->assertOk()
            ->assertDontSee('Archived Member');
    }

    public function test_archive_page_offers_type_switches_and_restores_members(): void
    {
        $admin = $this->admin();
        $member = $this->member(['name' => 'Archived Member']);
        $member->delete();

        $this->actingAs($admin)
            ->get(route('admin.archive'))
            ->assertOk()
            ->assertSee('Account Users')
            ->assertSee('Members')
            ->assertSee("Farmer's Harvest");

        $this->actingAs($admin)
            ->get(route('admin.archive', ['type' => 'members']))
            ->assertOk()
            ->assertSee('Archived Member')
            ->assertSee('Restore');

        $this->actingAs($admin)
            ->post(route('admin.archive.members.restore', $member->id))
            ->assertRedirect(route('admin.archive', ['type' => 'members']));

        $this->assertNull($member->fresh()->archived_at);
    }
}
