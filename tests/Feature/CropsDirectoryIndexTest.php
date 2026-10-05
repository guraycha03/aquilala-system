<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CropsDirectoryIndexTest extends TestCase
{
    use RefreshDatabase;

    private function makeFarmer(): User
    {
        return User::create([
            'name' => 'Maria Santos',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'farmer_'.uniqid().'@example.com',
            'role' => 'farmer',
            'password' => 'password123',
        ]);
    }

    public function test_directory_page_renders_library_crops_with_agronomy_and_varieties(): void
    {
        $response = $this->actingAs($this->makeFarmer())
            ->get(route('farmer.crops-directory.index'));

        $response->assertOk();

        // Every crop in the library is listed.
        $response->assertSee('43 Crops');
        $response->assertSee('Rice');
        $response->assertSee('Sugarcane');

        // Agronomy + varieties now render instead of em dashes.
        $response->assertSee('Varieties grown in Sorsogon');
        $response->assertSee('NSIC Rc 18 (Ala)');
        $response->assertSee('Days to Harvest');

        // Dropped, non-Sorsogon crops are gone.
        $response->assertDontSee('Tobacco');
        $response->assertDontSee('Wheat');
    }

    public function test_directory_search_blob_includes_aliases(): void
    {
        $response = $this->actingAs($this->makeFarmer())
            ->get(route('farmer.crops-directory.index'));

        $response->assertOk();
        $response->assertSee('palay', false);
    }

    public function test_guest_is_redirected_away_from_the_directory(): void
    {
        $this->get(route('farmer.crops-directory.index'))
            ->assertRedirect();
    }
}
