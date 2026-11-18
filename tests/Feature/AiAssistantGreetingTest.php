<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Greeting and conversational behaviour across user roles.
 *
 * The assistant must feel human: a greeting gets a greeting back, regardless
 * of role. It must also handle random/unclear messages gracefully without
 * dumping irrelevant database data.
 */
class AiAssistantGreetingTest extends TestCase
{
    use RefreshDatabase;

    private User $farmer;

    private User $store;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.groq.key', null);

        $this->farmer = $this->makeUser('farmer', 'juan_' . uniqid() . '@example.test');
        $this->store = $this->makeUser('store_owner', 'store_' . uniqid() . '@example.test');
        $this->admin = $this->makeUser('admin', 'admin_' . uniqid() . '@example.test');

        $fertilizer = $this->makeCategory('Fertilizers', 'fertilizers');
        $this->makeProduct($this->store, $fertilizer, 'Premium Urea 46-0-0', 'UREA-46', 1450.00, 40);
    }

    // ────────────────────────────────────────────────────────────
    //  Greeting responses by role
    // ────────────────────────────────────────────────────────────

    public function test_farmer_greeting_gets_greeting_back(): void
    {
        $response = $this->askFarmer('Good morning!');

        $response->assertOk();

        $reply = strtolower($response->json('reply'));
        $this->assertStringContainsString('good', $reply, "Expected a greeting in reply, got: {$response->json('reply')}");
    }

    public function test_store_owner_greeting_gets_greeting_back(): void
    {
        $response = $this->askStore('Hello there!');

        $response->assertOk();

        $reply = strtolower($response->json('reply'));
        $this->assertStringContainsString('hello', $reply, "Expected a greeting in reply, got: {$response->json('reply')}");
    }

    public function test_admin_greeting_gets_greeting_back(): void
    {
        $response = $this->askAdmin('Hi, good evening!');

        $response->assertOk();

        $reply = strtolower($response->json('reply'));
        $this->assertStringContainsString('good', $reply, "Expected a greeting in reply, got: {$response->json('reply')}");
    }

    // ────────────────────────────────────────────────────────────
    //  Greeting followed by a real question
    // ────────────────────────────────────────────────────────────

    public function test_greeting_then_question_gets_both(): void
    {
        $response = $this->askFarmer('Good morning! What fertilizers do you have for rice?');

        $response->assertOk();

        $reply = strtolower($response->json('reply'));
        $this->assertStringContainsString('good', $reply, "Expected greeting prefix, got: {$response->json('reply')}");
        $this->assertStringContainsString('rice', $reply, "Expected rice answer, got: {$response->json('reply')}");
    }

    // ────────────────────────────────────────────────────────────
    //  Random / unclear messages
    // ────────────────────────────────────────────────────────────

    public function test_random_string_gets_helpful_response_not_data_dump(): void
    {
        $response = $this->askFarmer('xyzzy plugh foo bar');

        $response->assertOk();

        $reply = $response->json('reply');
        $this->assertNotSame('', $reply, 'Reply should not be empty');

        // Should NOT dump unrelated database data
        $this->assertStringNotContainsString('1,450.00', $reply, 'Should not dump product prices for unrelated message');
        $this->assertStringNotContainsString('UREA-46', $reply, 'Should not dump product codes for unrelated message');
    }

    public function test_emoji_only_message_gets_response(): void
    {
        $response = $this->askFarmer('👍🙏🎉');

        $response->assertOk();

        $this->assertNotSame('', $response->json('reply'), 'Reply should not be empty');
    }

    public function test_single_word_random_gets_response(): void
    {
        $response = $this->askStore('asdfghjkl');

        $response->assertOk();

        $this->assertNotSame('', $response->json('reply'), 'Reply should not be empty');
    }

    // ────────────────────────────────────────────────────────────
    //  Unrelated topics get a clear explanation
    // ────────────────────────────────────────────────────────────

    public function test_completely_unrelated_question_gets_explanation_not_refusal(): void
    {
        $response = $this->askFarmer('What is the capital of France?');

        $response->assertOk();

        $reply = strtolower($response->json('reply'));

        // Should explain WHY it can't answer, not just refuse vaguely
        $this->assertStringContainsString('help', $reply, "Expected explanation of purpose, got: {$response->json('reply')}");
    }

    public function test_weather_question_gets_explanation(): void
    {
        $response = $this->askFarmer('Will it rain tomorrow in Manila?');

        $response->assertOk();

        $reply = strtolower($response->json('reply'));
        $this->assertStringContainsString('help', $reply, "Expected explanation of purpose, got: {$response->json('reply')}");
    }

    // ────────────────────────────────────────────────────────────
    //  Role-based access: admin farmer activity
    // ────────────────────────────────────────────────────────────

    public function test_admin_can_ask_about_farmer_activity(): void
    {
        $response = $this->askAdmin('How many farmers are active?');

        $response->assertOk();

        $reply = strtolower($response->json('reply'));
        // Should answer from ActivityLog, not refuse or dump harvest data
        $this->assertStringNotContainsString('unrelated', $reply, 'Should not say unrelated');
    }

    public function test_farmer_cannot_ask_about_other_farmers(): void
    {
        $response = $this->askFarmer('How many farmers are active?');

        $response->assertOk();

        $reply = strtolower($response->json('reply'));
        $this->assertNotSame('', $reply, 'Reply should not be empty');
        // The association-wide activity answer is admin only: a farmer must
        // never see other farmers' activity records.
        $this->assertStringNotContainsString('farmer activity records', $reply, "Farmer should not see admin activity data, got: {$response->json('reply')}");
    }

    // ────────────────────────────────────────────────────────────
    //  No data dumping from old/unrelated years
    // ────────────────────────────────────────────────────────────

    public function test_no_harvest_dump_for_greeting(): void
    {
        $response = $this->askFarmer('Good morning!');

        $response->assertOk();

        $reply = $response->json('reply');
        // Should not dump harvest quantities or years
        $this->assertDoesNotMatchRegularExpression('/\d{4}/', $reply, "Greeting should not include years, got: {$reply}");
        $this->assertStringNotContainsString('hectare', strtolower($reply), 'Greeting should not dump harvest data');
    }

    public function test_no_harvest_dump_for_unrelated_question(): void
    {
        $response = $this->askFarmer('What is the meaning of life?');

        $response->assertOk();

        $reply = $response->json('reply');
        $this->assertDoesNotMatchRegularExpression('/\d{4}/', $reply, "Unrelated question should not include years, got: {$reply}");
        $this->assertStringNotContainsString('hectare', strtolower($reply), 'Unrelated question should not dump harvest data');
    }

    // ────────────────────────────────────────────────────────────
    //  The greeting belongs to its own turn
    // ────────────────────────────────────────────────────────────

    public function test_greeting_does_not_leak_into_the_next_turn(): void
    {
        $first = $this->askFarmer('Good morning! What fertilizers do you have for rice?');
        $first->assertOk();
        $this->assertStringContainsString('good', strtolower($first->json('reply')));

        // The context the assistant handed back is what the browser sends on
        // the next message; the greeting must not ride along in it.
        $second = $this->askFarmer('Do you have urea?', $first->json('context'));
        $second->assertOk();

        $reply = strtolower($second->json('reply'));
        $this->assertStringNotContainsString(
            'good morning! how can i help',
            $reply,
            "Yesterday's greeting leaked into this turn: {$second->json('reply')}"
        );
    }

    // ────────────────────────────────────────────────────────────
    //  Helpers
    // ────────────────────────────────────────────────────────────

    private function askFarmer(string $message, ?array $context = null)
    {
        return $this->actingAs($this->farmer)->postJson(route('farmer.ai-chat.send'), [
            'messages' => [['role' => 'user', 'content' => $message]],
            'context' => $context,
        ]);
    }

    private function askStore(string $message, ?array $context = null)
    {
        return $this->actingAs($this->store)->postJson(route('store.ai-chat.send'), [
            'messages' => [['role' => 'user', 'content' => $message]],
            'context' => $context,
        ]);
    }

    private function askAdmin(string $message, ?array $context = null)
    {
        return $this->actingAs($this->admin)->postJson(route('admin.ai-chat.send'), [
            'messages' => [['role' => 'user', 'content' => $message]],
            'context' => $context,
        ]);
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create([
            'name'       => $role === 'store_owner' ? 'Green Valley Agri Supply' : ($role === 'admin' ? 'System Admin' : 'Juan Dela Cruz'),
            'first_name' => $role === 'store_owner' ? 'Green' : ($role === 'admin' ? 'System' : 'Juan'),
            'last_name'  => $role === 'store_owner' ? 'Valley' : ($role === 'admin' ? 'Admin' : 'Dela Cruz'),
            'email'      => $email,
            'role'       => $role,
            'password'   => 'password123',
        ]);
    }

    private function makeCategory(string $name, string $slug): Category
    {
        return Category::create([
            'name'        => $name,
            'slug'        => $slug,
            'description' => $name . ' inputs',
        ]);
    }

    private function makeProduct(
        User $store,
        Category $category,
        string $name,
        string $sku,
        float $price,
        int $stock,
    ): Product {
        return Product::create([
            'user_id'     => $store->id,
            'name'        => $name,
            'sku'         => $sku,
            'category_id' => $category->id,
            'category'    => $category->name,
            'price'       => $price,
            'stock_level' => $stock,
        ]);
    }
}
