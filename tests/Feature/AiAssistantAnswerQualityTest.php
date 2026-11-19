<?php

namespace Tests\Feature;

use App\AI\AiConversationContext;
use App\AI\AiIntent;
use App\AI\AiIntentRouter;
use App\Models\Category;
use App\Models\Crop;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Regression for the second reported thread. The slot flow had been fixed,
 * and the conversation then tripped over four answer-quality failures:
 *
 *   1. "i am just asking for you to list fertilizers" was refused with "no
 *      fertilizer products listed" while seven sat in the catalogue — the
 *      search required the filler word "asking" inside every product, and
 *      the plural never matched the singular category;
 *   2. "How can I improve rice yield?" was answered with the crop's fact
 *      sheet and the rate warning;
 *   3. "What can I do about yellowing leaves?" was answered with the fact
 *      sheet when a crop was inherited and with the capability list when it
 *      was not — both answer a question nobody asked;
 *   4. "can you help me with planting problems?" already worked and must
 *      keep the answer it gave.
 *
 * No provider is configured, so every assertion is on deterministic output.
 */
class AiAssistantAnswerQualityTest extends TestCase
{
    use RefreshDatabase;

    private User $farmer;

    private User $store;

    private Product $urea;

    protected function setUp(): void
    {
        parent::setUp();

        // No provider: these scenarios assert the deterministic flow with no
        // model in the loop, and the suite must never reach a live API.
        Config::set('services.ai.provider', 'groq');
        Config::set('services.groq.key', null);

        $this->farmer = $this->makeUser('farmer', 'juan_' . uniqid() . '@example.test');
        $this->store = $this->makeUser('store_owner', 'store_' . uniqid() . '@example.test');

        $fertilizer = $this->makeCategory('Fertilizers', 'fertilizers');
        $seeds = $this->makeCategory('Seeds', 'seeds');

        $this->urea = $this->makeProduct($this->store, $fertilizer, 'Premium Urea 46-0-0', 'UREA-46', 1450.00, 40);
        $this->makeProduct($this->store, $seeds, 'Inbred Rice Seeds NSIC Rc222', 'RICE-RC222', 1250.00, 60);

        // The crops directory the no-crop answers list from. RefreshDatabase
        // migrates only, so without these "tell me which crop" has nothing
        // to offer the farmer.
        Crop::create(['name' => 'Rice', 'category' => 'Cereal', 'description' => 'Palay / Rice (Oryza sativa)']);
        Crop::create(['name' => 'Corn', 'category' => 'Cereal', 'description' => 'Maize (Zea mays)']);
    }

    // ────────────────────────────────────────────────────────────
    //  1. The listing refusal
    // ────────────────────────────────────────────────────────────

    public function test_conversational_filler_does_not_empty_the_search(): void
    {
        $search = app(ProductSearchService::class);

        $keywords = $search->keywords('i am just asking for you to list fertilizers');

        $this->assertNotContains('asking', $keywords, 'Filler describes how the question was asked, not the product.');
        $this->assertContains('fertilizer', $keywords, 'The plural must stem to the singular the catalogue holds.');
        $this->assertNotEmpty($search->search('i am just asking for you to list fertilizers'));
        $this->assertNotEmpty($search->search('list fertilizers'));
    }

    public function test_listing_fertilizers_returns_the_products_not_a_refusal(): void
    {
        $response = $this->ask('i am just asking for you to list fertilizers');

        $response->assertOk();
        $this->assertSame(AiIntent::PRODUCT_SEARCH, $response->json('intent'));
        $this->assertSame('product_results', $response->json('type'));

        $reply = $response->json('reply');
        $this->assertStringContainsString('I found', $reply);
        $this->assertStringContainsString('Premium Urea', $reply);

        $ids = collect($response->json('suggestions'))->pluck('id');
        $this->assertTrue($ids->contains($this->urea->id));
    }

    // ────────────────────────────────────────────────────────────
    //  2. "How can I improve rice yield?"
    // ────────────────────────────────────────────────────────────

    public function test_improving_yield_is_answered_with_the_care_process_not_the_fact_sheet(): void
    {
        $response = $this->ask('How can I improve rice yield?');

        $response->assertOk();
        $this->assertSame(AiIntent::FARMING_ADVICE, $response->json('intent'));

        $reply = $response->json('reply');
        $this->assertStringContainsString('usual process is', $reply);
        $this->assertStringNotContainsString(
            'I do not give fertilizer rates',
            $reply,
            'The rate warning answers a question the farmer did not ask.'
        );
        $this->assertSame('answer', $response->json('type'));
    }

    // ────────────────────────────────────────────────────────────
    //  3. "What can I do about yellowing leaves?" — with and without context
    // ────────────────────────────────────────────────────────────

    public function test_a_plant_symptom_with_a_crop_is_not_answered_with_the_fact_sheet(): void
    {
        $context = (new AiConversationContext(
            lastIntent: AiIntent::CROP_ADVICE,
            lastCrop: 'Rice',
        ))->toArray();

        $response = $this->ask('What can I do about yellowing leaves?', $context);

        $response->assertOk();
        $this->assertSame(AiIntent::FARMING_ADVICE, $response->json('intent'));

        $reply = $response->json('reply');
        $this->assertStringContainsString('diagnose', $reply, 'The first line must say what will not be guessed.');
        $this->assertStringContainsString('usual process is', $reply, 'The approved guidance is what can be given.');
        $this->assertStringNotContainsString(
            'I do not give fertilizer rates',
            $reply,
            'The rate warning answers a question the farmer did not ask.'
        );
        $this->assertStringNotContainsString(
            'I can help with farming, Aquilala products',
            $reply,
            'A symptom is not an out-of-scope question.'
        );
    }

    public function test_a_plant_symptom_without_context_asks_for_the_crop_not_the_capability_list(): void
    {
        // What the reporter actually saw: their context had been lost by this
        // turn, and the symptom fell through to the capability list.
        $response = $this->ask('What can I do about yellowing leaves?');

        $response->assertOk();
        $this->assertSame(AiIntent::FARMING_ADVICE, $response->json('intent'));

        $reply = $response->json('reply');
        $this->assertStringContainsString('diagnose', $reply);
        $this->assertStringContainsString('Mga tanim na pwede', $reply, 'Naming the crops is how the farmer answers.');
        $this->assertStringNotContainsString('I can help with farming, Aquilala products', $reply);
    }

    // ────────────────────────────────────────────────────────────
    //  4. The topic question that already worked, plus diagnose phrasing
    // ────────────────────────────────────────────────────────────

    public function test_a_planting_topics_question_keeps_its_crop_list_answer(): void
    {
        $response = $this->ask('can you help me with planting problems?');

        $response->assertOk();
        $this->assertSame(AiIntent::FARMING_ADVICE, $response->json('intent'));

        $reply = $response->json('reply');
        $this->assertStringContainsString('keen to get planting', $reply);
        $this->assertStringNotContainsString('diagnose', $reply, 'A topic question is not a symptom report.');
    }

    public function test_diagnosing_a_crop_reaches_the_advice_branch(): void
    {
        $routed = app(AiIntentRouter::class)->route('Can you diagnose my rice?');

        $this->assertSame(AiIntent::FARMING_ADVICE, $routed['intent']);
        $this->assertTrue($routed['plant_problem']);
    }

    // ────────────────────────────────────────────────────────────
    //  The router flags behind the wording
    // ────────────────────────────────────────────────────────────

    public function test_yield_and_symptom_messages_route_to_farming_advice(): void
    {
        $router = app(AiIntentRouter::class);

        $yield = $router->route('How can I improve rice yield?');
        $this->assertSame(AiIntent::FARMING_ADVICE, $yield['intent']);
        $this->assertFalse($yield['plant_problem']);

        $symptom = $router->route('What can I do about yellowing leaves?');
        $this->assertSame(AiIntent::FARMING_ADVICE, $symptom['intent']);
        $this->assertTrue($symptom['plant_problem']);

        $topic = $router->route('can you help me with planting problems?');
        $this->assertSame(AiIntent::FARMING_ADVICE, $topic['intent']);
        $this->assertFalse($topic['plant_problem'], 'A topic question must not be worded as a symptom report.');
    }

    // ────────────────────────────────────────────────────────────
    //  Helpers
    // ────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>|null  $context
     */
    private function ask(string $message, ?array $context = null)
    {
        return $this->actingAs($this->farmer)->postJson(route('farmer.ai-chat.send'), [
            'messages' => [['role' => 'user', 'content' => $message]],
            'context' => $context,
        ]);
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create([
            'name'       => $role === 'store_owner' ? 'Green Valley Agri Supply' : 'Juan Dela Cruz',
            'first_name' => $role === 'store_owner' ? 'Green' : 'Juan',
            'last_name'  => $role === 'store_owner' ? 'Valley' : 'Dela Cruz',
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
            'user_id'           => $store->id,
            'category_id'       => $category->id,
            'category'          => $category->name,
            'sku'               => $sku,
            'name'              => $name,
            'price'             => $price,
            'price_unit'        => 'sack',
            'stock_quantity'    => $stock,
            'stock_unit'        => 'sacks',
            'reorder_threshold' => 5,
            'status'            => 'active',
        ]);
    }
}
