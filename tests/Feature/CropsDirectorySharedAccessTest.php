<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The crop reference library is the only page officials (admin) and members
 * (farmer) share. Both roles get the very same controller, view and data;
 * every other page stays behind its own role guard.
 */
class CropsDirectorySharedAccessTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role): User
    {
        return User::create([
            'name' => 'Test User',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $role.'_'.uniqid().'@example.com',
            'role' => $role,
            'password' => 'password123',
        ]);
    }

    public function test_admin_and_farmer_render_the_same_shared_directory(): void
    {
        $farmer = $this->actingAs($this->member('farmer'))
            ->get(route('farmer.crops-directory.index'));
        $admin = $this->actingAs($this->member('admin'))
            ->get(route('farmer.crops-directory.index'));

        $farmer->assertOk()->assertViewIs('farmer.crops-directory.index');
        $admin->assertOk()->assertViewIs('farmer.crops-directory.index');

        // Same crop library, same data — no second page or design for admins.
        $this->assertEquals($farmer->viewData('crops'), $admin->viewData('crops'));
        $this->assertEquals($farmer->viewData('supportedCrops'), $admin->viewData('supportedCrops'));
        $admin->assertSee('43 Verified Crops');

        // The breadcrumb points each role at its own dashboard, never the other's.
        $admin->assertSee(route('dashboard.admin'), false)->assertDontSee(route('dashboard.farmer'), false);
        $farmer->assertSee(route('dashboard.farmer'), false)->assertDontSee(route('dashboard.admin'), false);

        // Even while on the shared URL, the surrounding navigation stays
        // entirely role-specific — no farmer links in admin, none in farmer.
        $admin->assertSee('Admin Control')->assertDontSee('Farmer Dashboard')->assertDontSee('My Crops');
        $farmer->assertSee('Farmer Dashboard')->assertDontSee('Admin Control');

        $this->assertFalse(Route::has('admin.operations.crop-directory'));
        $this->assertFalse(view()->exists('admin.operations.crop-directory'));
    }

    public function test_sharing_one_page_does_not_open_any_other_role_space(): void
    {
        $this->actingAs($this->member('admin'))
            ->get(route('farmer.crops.index'))
            ->assertForbidden();

        $this->actingAs($this->member('farmer'))
            ->get(route('admin.operations.catalog'))
            ->assertForbidden();
    }
}
