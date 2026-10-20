<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The AI chat header avatar is changeable and saved per account:
 *
 *   - POST avatar lands on the public disk and the path is stored on the
 *     users row, so the choice follows the account on every device;
 *   - the chat page renders it in the header and beside every AI reply;
 *   - accounts that never upload see the shared placeholder image;
 *   - replacing the avatar removes the previous file.
 */
class AiChatAvatarTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $name, string $role): User
    {
        return User::create([
            'name' => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? 'Test',
            'email' => strtolower(str_replace(' ', '_', $name)).'_'.uniqid().'@example.com',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
            'password' => 'password123',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function farmer_can_upload_an_ai_avatar_that_is_saved_per_account(): void
    {
        Storage::fake('public');
        $farmer = $this->makeUser('Ana Farmer', 'farmer');

        $response = $this->actingAs($farmer)->postJson(route('farmer.ai-chat.avatar'), [
            'avatar' => UploadedFile::fake()->image('assistant.png', 200, 200),
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $farmer->refresh();
        $this->assertNotNull($farmer->ai_avatar);
        Storage::disk('public')->assertExists($farmer->ai_avatar);
        $this->assertStringContainsString($farmer->ai_avatar, (string) $response->json('url'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function replacing_the_avatar_removes_the_previous_file(): void
    {
        Storage::fake('public');
        $farmer = $this->makeUser('Bay Farmer', 'farmer');

        $this->actingAs($farmer)->postJson(route('farmer.ai-chat.avatar'), [
            'avatar' => UploadedFile::fake()->image('first.png', 100, 100),
        ])->assertOk();
        $first = $farmer->fresh()->ai_avatar;

        $this->actingAs($farmer)->postJson(route('farmer.ai-chat.avatar'), [
            'avatar' => UploadedFile::fake()->image('second.png', 100, 100),
        ])->assertOk();

        $second = $farmer->fresh()->ai_avatar;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function chat_page_shows_the_saved_avatar_and_falls_back_to_the_placeholder(): void
    {
        Storage::fake('public');
        $farmer = $this->makeUser('Cara Farmer', 'farmer');

        // Before any upload the shared placeholder is used.
        $this->actingAs($farmer)->get(route('farmer.ai-chat.index'))
            ->assertOk()
            ->assertSee('images/ai-avatar.png', false);

        $this->actingAs($farmer)->postJson(route('farmer.ai-chat.avatar'), [
            'avatar' => UploadedFile::fake()->image('mine.png', 100, 100),
        ])->assertOk();

        $this->actingAs($farmer->fresh())->get(route('farmer.ai-chat.index'))
            ->assertOk()
            ->assertSee($farmer->ai_avatar, false)
            ->assertSee('data-ai-avatar', false);

        // A different account keeps its own (default) avatar — nothing leaks.
        $other = $this->makeUser('Dan Farmer', 'farmer');
        $this->actingAs($other)->get(route('farmer.ai-chat.index'))
            ->assertOk()
            ->assertSee('images/ai-avatar.png', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function store_owner_avatar_uses_the_store_route(): void
    {
        Storage::fake('public');
        $store = $this->makeUser('Green Store', 'store_owner');

        $this->actingAs($store)->postJson(route('store.ai-chat.avatar'), [
            'avatar' => UploadedFile::fake()->image('shop.png', 100, 100),
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertNotNull($store->fresh()->ai_avatar);
        Storage::disk('public')->assertExists($store->fresh()->ai_avatar);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function non_image_files_are_rejected(): void
    {
        Storage::fake('public');
        $farmer = $this->makeUser('Eva Farmer', 'farmer');

        $this->actingAs($farmer)->postJson(route('farmer.ai-chat.avatar'), [
            'avatar' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ])->assertStatus(422);

        $this->assertNull($farmer->fresh()->ai_avatar);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function guests_cannot_upload_an_avatar(): void
    {
        Storage::fake('public');

        $this->postJson(route('farmer.ai-chat.avatar'), [
            'avatar' => UploadedFile::fake()->image('guest.png', 50, 50),
        ])->assertStatus(401);
    }
}
