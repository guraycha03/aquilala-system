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
        $response->assertSee('43 Verified Crops');
        $response->assertSee('Rice');
        $response->assertSee('Sugarcane');

        // Agronomy + varieties now render instead of em dashes.
        $response->assertSee('Varieties Grown in Sorsogon');
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

    public function test_config_image_resolves_from_the_crops_folder(): void
    {
        $response = $this->actingAs($this->makeFarmer())
            ->get(route('farmer.crops-directory.index'));

        $response->assertOk();
        $response->assertSee('images/crops/rice.png', false);
    }

    public function test_a_dropped_image_in_the_crops_folder_is_auto_detected(): void
    {
        // Okra has no config image and no file — the shape of every crop a
        // farmer-facing admin drops a new photo for. Before the resolver
        // looked in public/images/crops/ this always fell back to the
        // placeholder no matter what file was added.
        $file = public_path('images/crops/okra.jpg');
        $created = ! file_exists($file);

        try {
            if ($created) {
                file_put_contents($file, 'test-image');
            }

            $response = $this->actingAs($this->makeFarmer())
                ->get(route('farmer.crops-directory.index'));

            $response->assertOk();
            $response->assertSee('images/crops/okra.jpg', false);
        } finally {
            if ($created && file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function test_a_fresh_jpg_wins_over_the_older_png_of_the_same_crop(): void
    {
        // corn.png has existed since the first version of the page; a corn.jpg
        // dropped next to it is the newer picture and must be the one shown.
        $file = public_path('images/crops/corn.jpg');
        $created = ! file_exists($file);

        try {
            if ($created) {
                file_put_contents($file, 'test-image');
            }

            $response = $this->actingAs($this->makeFarmer())
                ->get(route('farmer.crops-directory.index'));

            $response->assertOk();
            $response->assertSee('images/crops/corn.jpg', false);
            $response->assertDontSee('images/crops/corn.png', false);
        } finally {
            if ($created && file_exists($file)) {
                unlink($file);
            }
        }
    }
}
