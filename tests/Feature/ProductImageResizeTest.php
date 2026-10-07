<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageResizeTest extends TestCase
{
    use RefreshDatabase;

    private function requireGd(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD is not available in this PHP build.');
        }
    }

    private function storeOwner(): User
    {
        return User::create([
            'name' => 'Green Valley Store',
            'first_name' => 'Green',
            'last_name' => 'Valley',
            'email' => 'store_' . uniqid() . '@example.com',
            'role' => 'store_owner',
            'password' => 'password123',
        ]);
    }

    private function category(): Category
    {
        return Category::create([
            'name' => 'Fertilizer',
            'slug' => 'fertilizer-' . uniqid(),
        ]);
    }

    private function payload(User $store, Category $category, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Urea',
            'sku' => 'SKU-' . uniqid(),
            'category' => 'Fertilizer',
            'category_id' => $category->id,
            'price' => 100,
            'stock_quantity' => 10,
            'reorder_threshold' => 2,
            'recommended_season' => 'both',
            'status' => 'active',
        ], $overrides);
    }

    /**
     * Build a PNG payload at level 0 (uncompressed) so it is far bigger than
     * any re-encoded result: the opaque variant must flatten to JPEG, the
     * transparent one must survive as PNG.
     */
    private function png(int $width, int $height, bool $transparent): string
    {
        $image = imagecreatetruecolor($width, $height);

        if ($transparent) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
            imagefilledellipse(
                $image,
                (int) ($width / 2),
                (int) ($height / 2),
                (int) ($width / 2),
                (int) ($height / 2),
                imagecolorallocate($image, 200, 40, 40)
            );
        } else {
            imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, imagecolorallocate($image, 30, 90, 160));
        }

        ob_start();
        imagepng($image, null, 0);
        $bytes = ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    public function test_a_full_size_photo_is_downscaled_to_the_max_edge_on_upload(): void
    {
        $this->requireGd();
        Storage::fake('public');
        $store = $this->storeOwner();
        $category = $this->category();

        $upload = UploadedFile::fake()->image('field.jpg', 3000, 2000);
        $uploadedBytes = $upload->getSize();

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($store, $category, [
            'images' => [$upload],
        ]))->assertRedirect();

        $image = Product::where('name', 'Test Urea')->firstOrFail()->images()->firstOrFail();
        $stored = Storage::disk('public')->get($image->file_path);
        $dims = getimagesizefromstring($stored);

        $this->assertSame(1600, $dims[0], 'Longest edge must be capped at MAX_EDGE.');
        $this->assertSame(1067, $dims[1], 'Aspect ratio must be preserved.');
        $this->assertSame(IMAGETYPE_JPEG, $dims[2]);
        $this->assertStringEndsWith('.jpg', $image->file_path);
        $this->assertLessThan($uploadedBytes, strlen($stored));
        $this->assertSame(strlen($stored), (int) $image->file_size, 'file_size must describe the stored file.');
    }

    public function test_a_transparent_png_stays_a_png_and_keeps_its_capped_dimensions(): void
    {
        $this->requireGd();
        Storage::fake('public');
        $store = $this->storeOwner();
        $category = $this->category();

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($store, $category, [
            'images' => [UploadedFile::fake()->createWithContent('label.png', $this->png(1700, 700, true))],
        ]))->assertRedirect();

        $image = Product::where('name', 'Test Urea')->firstOrFail()->images()->firstOrFail();
        $dims = getimagesizefromstring(Storage::disk('public')->get($image->file_path));

        $this->assertSame(1600, $dims[0]);
        $this->assertSame(659, $dims[1]);
        $this->assertSame(IMAGETYPE_PNG, $dims[2]);
        $this->assertStringEndsWith('.png', $image->file_path);
    }

    public function test_an_opaque_png_photo_is_flattened_to_jpeg(): void
    {
        $this->requireGd();
        Storage::fake('public');
        $store = $this->storeOwner();
        $category = $this->category();
        $upload = UploadedFile::fake()->createWithContent('photo.png', $this->png(1700, 700, false));

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($store, $category, [
            'images' => [$upload],
        ]))->assertRedirect();

        $image = Product::where('name', 'Test Urea')->firstOrFail()->images()->firstOrFail();
        $stored = Storage::disk('public')->get($image->file_path);
        $dims = getimagesizefromstring($stored);

        $this->assertSame(IMAGETYPE_JPEG, $dims[2], 'An opaque PNG carries no alpha, so it must flatten to JPEG.');
        $this->assertSame(1600, $dims[0]);
        $this->assertSame(659, $dims[1]);
        $this->assertStringEndsWith('.jpg', $image->file_path);
        $this->assertLessThan(1_000_000, strlen($stored), 'The PNG payload must collapse to a fraction of its size.');
        $this->assertSame('image/jpeg', $image->mime_type, 'mime_type must describe the stored file, not the upload.');
        $this->assertSame(strlen($stored), (int) $image->file_size);
    }

    public function test_an_upload_is_paired_with_a_smaller_thumbnail(): void
    {
        $this->requireGd();
        Storage::fake('public');
        $store = $this->storeOwner();
        $category = $this->category();

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($store, $category, [
            'images' => [UploadedFile::fake()->image('field.jpg', 3000, 2000)],
        ]))->assertRedirect();

        $image = Product::where('name', 'Test Urea')->firstOrFail()->images()->firstOrFail();

        $this->assertNotNull($image->thumb_path, 'A decodable upload must get a thumbnail.');

        $main = Storage::disk('public')->get($image->file_path);
        $thumb = Storage::disk('public')->get($image->thumb_path);
        $dims = getimagesizefromstring($thumb);

        $this->assertSame(400, $dims[0]);
        $this->assertSame(267, $dims[1]);
        $this->assertSame(IMAGETYPE_JPEG, $dims[2]);
        $this->assertLessThan(strlen($main), strlen($thumb));
        $this->assertStringEndsWith('.thumb.jpg', $image->thumb_path);
        $this->assertSame(
            pathinfo($image->file_path, PATHINFO_FILENAME) . '.thumb',
            pathinfo($image->thumb_path, PATHINFO_FILENAME),
            'The thumbnail must sit beside its full-size file under the same name.'
        );
    }

    public function test_a_product_card_serves_the_thumbnail_not_the_full_image(): void
    {
        $this->requireGd();
        Storage::fake('public');
        $store = $this->storeOwner();
        $category = $this->category();

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($store, $category, [
            'images' => [UploadedFile::fake()->image('field.jpg', 3000, 2000)],
        ]))->assertRedirect();

        $product = Product::where('name', 'Test Urea')->firstOrFail();
        $html = Blade::render('<x-product-card :product="$product" />', ['product' => $product]);

        $this->assertStringContainsString('storage/' . $product->mainImage->thumb_path, $html);
        $this->assertStringNotContainsString('storage/' . $product->mainImage->file_path . '"', $html);
    }

    public function test_an_already_small_image_is_stored_untouched_and_still_thumbed(): void
    {
        $this->requireGd();
        Storage::fake('public');
        $store = $this->storeOwner();
        $category = $this->category();

        $upload = UploadedFile::fake()->image('thumb.jpg', 800, 600);
        $uploadedBytes = $upload->getSize();

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($store, $category, [
            'images' => [$upload],
        ]))->assertRedirect();

        $image = Product::where('name', 'Test Urea')->firstOrFail()->images()->firstOrFail();
        $stored = Storage::disk('public')->get($image->file_path);
        $dims = getimagesizefromstring($stored);

        $this->assertSame($uploadedBytes, strlen($stored), 'A small image must not be re-encoded.');
        $this->assertSame(800, $dims[0]);
        $this->assertSame(600, $dims[1]);

        $this->assertNotNull($image->thumb_path, 'An under-cap image still gets a thumbnail.');

        $thumb = getimagesizefromstring(Storage::disk('public')->get($image->thumb_path));

        $this->assertSame(400, $thumb[0]);
        $this->assertSame(300, $thumb[1]);
    }

    public function test_a_file_without_image_data_is_stored_as_is(): void
    {
        Storage::fake('public');
        $store = $this->storeOwner();
        $category = $this->category();

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($store, $category, [
            'images' => [UploadedFile::fake()->create('a.jpg', 20, 'image/jpeg')],
        ]))->assertRedirect();

        $image = Product::where('name', 'Test Urea')->firstOrFail()->images()->firstOrFail();

        Storage::disk('public')->assertExists($image->file_path);
        $this->assertSame(1, Product::where('name', 'Test Urea')->firstOrFail()->images()->count());

        // Nothing could be decoded, so there is no thumbnail — and the card
        // views must fall back to the full image instead of rendering nothing.
        $this->assertNull($image->thumb_path);
        $this->assertSame($image->url, $image->thumb_url);
    }
}
