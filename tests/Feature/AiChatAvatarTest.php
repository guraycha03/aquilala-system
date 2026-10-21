<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AiAvatars;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The AI chat header avatar is chosen from the system's own set:
 *
 *   - picking one of ours stores the key on the users row, so the choice
 *     follows the account on every device;
 *   - nothing is uploaded: an unknown key is refused, and a file is not a
 *     key;
 *   - the chat page renders the chosen avatar in the header and offers the
 *     whole set in the picker;
 *   - an account that never chose sees the default.
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
    public function a_farmer_picks_one_of_the_system_avatars(): void
    {
        $farmer = $this->makeUser('Ana Farmer', 'farmer');

        $response = $this->actingAs($farmer)->postJson(route('farmer.ai-chat.avatar'), [
            'avatar' => 'leaf',
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $farmer->refresh();
        $this->assertSame('leaf', $farmer->ai_avatar);
        $this->assertStringContainsString(AiAvatars::all()['leaf']['file'], (string) $response->json('url'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function an_avatar_that_is_not_one_of_ours_is_refused(): void
    {
        $farmer = $this->makeUser('Bay Farmer', 'farmer');

        $this->actingAs($farmer)->postJson(route('farmer.ai-chat.avatar'), [
            'avatar' => 'my-own-photo.png',
        ])->assertStatus(422);

        $this->assertNull($farmer->fresh()->ai_avatar);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_file_is_not_a_choice(): void
    {
        $farmer = $this->makeUser('Cara Farmer', 'farmer');

        $this->actingAs($farmer)->postJson(route('farmer.ai-chat.avatar'), [
            'avatar' => \Illuminate\Http\Testing\File::create('me.png', 10),
        ])->assertStatus(422);

        $this->assertNull($farmer->fresh()->ai_avatar);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function the_chat_page_shows_the_picked_avatar_and_offers_the_whole_set(): void
    {
        $farmer = $this->makeUser('Dan Farmer', 'farmer');

        $this->actingAs($farmer)->postJson(route('farmer.ai-chat.avatar'), [
            'avatar' => 'leaf',
        ])->assertOk();

        $this->actingAs($farmer->fresh())->get(route('farmer.ai-chat.index'))
            ->assertOk()
            ->assertSee(AiAvatars::all()['leaf']['file'], false)
            ->assertSee('data-ai-avatar', false)
            ->assertSee('avatarPicker', false);

        // Every choice in the set is offered, and every one of them is a
        // file the system owns.
        foreach (AiAvatars::all() as $key => $avatar) {
            $this->actingAs($farmer->fresh())
                ->get(route('farmer.ai-chat.index'))
                ->assertOk()
                ->assertSee('data-avatar-key="' . $key . '"', false);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function an_account_that_never_chose_sees_the_default_avatar(): void
    {
        $farmer = $this->makeUser('Eva Farmer', 'farmer');

        $default = AiAvatars::all()[AiAvatars::defaultKey()]['file'];

        $this->actingAs($farmer)->get(route('farmer.ai-chat.index'))
            ->assertOk()
            ->assertSee($default, false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function store_owner_avatar_uses_the_store_route(): void
    {
        $store = $this->makeUser('Green Store', 'store_owner');

        $this->actingAs($store)->postJson(route('store.ai-chat.avatar'), [
            'avatar' => 'sprout',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertSame('sprout', $store->fresh()->ai_avatar);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function guests_cannot_pick_an_avatar(): void
    {
        $this->postJson(route('farmer.ai-chat.avatar'), [
            'avatar' => 'leaf',
        ])->assertStatus(401);
    }
}
