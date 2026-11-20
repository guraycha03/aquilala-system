<?php

namespace Tests\Feature;

use App\AI\AiIntent;
use App\AI\AiIntentRouter;
use App\Models\Category;
use App\Models\Crop;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * The Tagalog conversation reported from the live assistant:
 *
 *   "magandang gabi"                                   -> English "Hello"
 *   "pwede ka ba mag tagalog?"                         -> relied on the model
 *   "magrecommend ka nga produkto na fertilizer
 *    para sa tanim Kong sitaw"                         -> asked which crop
 *
 * The third case matters beyond the greeting: sitaw is a crop in the directory
 * with no decision-formula link to fertilizer. The assistant must still give a
 * real, useful recommendation from the catalogue plus general crop knowledge,
 * with product images and a crop-directory link.
 */
class AiAssistantTagalogRecommendationTest extends TestCase
{
    use RefreshDatabase;

    private User $farmer;

    private User $store;

    private Product $completeFertilizer;

    private Product $urea;

    private Product $sitawSeeds;

    private Product $riceSeeds;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.ai.provider', 'groq');
        Config::set('services.groq.key', null);
        Config::set('services.openrouter.key', null);

        $this->store = User::create([
            'name' => 'Green Valley Agri Supply', 'first_name' => 'Green', 'last_name' => 'Valley',
            'email' => 'store_' . uniqid() . '@example.test', 'role' => 'store_owner', 'password' => 'password123',
        ]);
        $this->farmer = User::create([
            'name' => 'Juan Dela Cruz', 'first_name' => 'Juan', 'last_name' => 'Dela Cruz',
            'email' => 'juan_' . uniqid() . '@example.test', 'role' => 'farmer', 'password' => 'password123',
        ]);

        $fertilizer = Category::create(['name' => 'Fertilizers', 'slug' => 'fertilizers', 'description' => 'Fertilizers']);
        $seeds = Category::create(['name' => 'Seeds', 'slug' => 'seeds', 'description' => 'Seeds']);

        $this->completeFertilizer = $this->product($fertilizer, 'Complete NPK 14-14-14');
        $this->urea = $this->product($fertilizer, 'Premium Urea 46-0-0');
        $this->sitawSeeds = $this->product($seeds, 'Sitaw (String Beans) Seeds');
        $this->riceSeeds = $this->product($seeds, 'Rice Seeds NSIC Rc222');

        Crop::create(['name' => 'Rice', 'category' => 'Cereal', 'description' => 'Palay / Rice']);
        Crop::create(['name' => 'String Beans', 'category' => 'Vegetable', 'description' => 'Sitaw / String Beans']);
        Crop::create(['name' => 'Ampalaya', 'category' => 'Vegetable', 'description' => 'Bitter gourd']);
    }

    public function test_tagalog_time_greeting_is_returned_in_the_same_language(): void
    {
        $reply = $this->ask('magandang gabi')->json('reply');

        $this->assertStringContainsString('Magandang gabi', $reply);
        $this->assertStringNotContainsString('Hello!', $reply);
    }

    public function test_english_time_greeting_still_works(): void
    {
        $this->assertStringContainsString('Good morning', $this->ask('good morning')->json('reply'));
        $this->assertStringContainsString('Good evening', $this->ask('good evening')->json('reply'));
    }

    public function test_language_question_is_answered_directly(): void
    {
        $response = $this->ask('pwede ka ba mag tagalog?');

        $response->assertOk();
        $reply = $response->json('reply');
        $this->assertStringContainsString('Oo', $reply);
        $this->assertStringContainsString('Tagalog', $reply);
    }

    public function test_router_understands_sitaw_and_the_fertilizer_shelf(): void
    {
        $routing = app(AiIntentRouter::class)->route(
            'magrecommend ka nga produkto na fertilizer para sa tanim Kong sitaw'
        );

        $this->assertSame(AiIntent::PRODUCT_RECOMMENDATION, $routing['intent']);
        $this->assertSame('String Beans', $routing['slots']['crop']);
        $this->assertSame('Fertilizer', $routing['slots']['input_category']);
        $this->assertTrue($routing['category_recommendation']);
    }

    public function test_tagalog_fertilizer_recommendation_answers_with_products_and_guidance(): void
    {
        $response = $this->ask('magrecommend ka nga produkto na fertilizer para sa tanim Kong sitaw');

        $response->assertOk();
        $this->assertSame(AiIntent::PRODUCT_RECOMMENDATION, $response->json('intent'));

        $reply = (string) $response->json('reply');
        $this->assertStringContainsString('sitaw', mb_strtolower($reply));
        $this->assertStringContainsString('String Beans', $reply);
        $this->assertStringContainsString('legume', $reply);
        $this->assertStringContainsString('nitrogen', mb_strtolower($reply));
        $this->assertStringNotContainsString('farm area', mb_strtolower($reply));
        $this->assertStringNotContainsString('Which crop', $reply);
        $this->assertStringContainsString('Crop Directory', $reply);
        $this->assertStringContainsString('/farmer/crops-directory', $reply);
        $this->assertStringContainsString('Complete NPK', $reply);

        $ids = collect($response->json('suggestions'))->pluck('id')->all();
        $this->assertContains($this->completeFertilizer->id, $ids);
        $this->assertContains($this->urea->id, $ids);
        $this->assertNotContains($this->sitawSeeds->id, $ids, 'A fertilizer question must not show seed.');
        $this->assertNotContains($this->riceSeeds->id, $ids);

        // Every card carries a real image URL built by ProductSearchService.
        foreach ($response->json('suggestions') as $card) {
            $this->assertArrayHasKey('image', $card);
            $this->assertNotEmpty($card['image']);
        }
    }

    public function test_english_fertilizer_recommendation_uses_the_same_path(): void
    {
        $response = $this->ask('recommend a fertilizer for my sitaw');

        $response->assertOk();
        $this->assertSame(AiIntent::PRODUCT_RECOMMENDATION, $response->json('intent'));
        $this->assertStringContainsString('Here are the products', (string) $response->json('reply'));
        $this->assertStringContainsString('legume', (string) $response->json('reply'));
        $this->assertNotEmpty($response->json('suggestions'));
    }

    public function test_seed_recommendation_ranks_the_crop_specific_seed_first(): void
    {
        $response = $this->ask('magrecommend ka ng binhi para sa sitaw');

        $response->assertOk();
        $this->assertSame(AiIntent::PRODUCT_RECOMMENDATION, $response->json('intent'));

        $ids = collect($response->json('suggestions'))->pluck('id')->all();
        $this->assertSame($this->sitawSeeds->id, $ids[0] ?? null);
        $this->assertContains($this->sitawSeeds->id, $ids);
        $this->assertNotContains($this->riceSeeds->id, $ids, 'Unrelated seed must not dilute a crop-specific request.');
        $this->assertNotContains($this->completeFertilizer->id, $ids);
    }

    public function test_legacy_formula_flow_still_asks_for_the_farm_area(): void
    {
        $response = $this->ask('What fertilizers for rice?');

        $response->assertOk();
        $this->assertSame(AiIntent::PRODUCT_RECOMMENDATION, $response->json('intent'));
        $this->assertStringContainsString('farm area', mb_strtolower((string) $response->json('reply')));
    }

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

    private function product(Category $category, string $name): Product
    {
        return Product::create([
            'user_id' => $this->store->id,
            'category_id' => $category->id,
            'category' => $category->name,
            'sku' => uniqid('SKU-'),
            'name' => $name,
            'price' => 1200.00,
            'price_unit' => 'pack',
            'stock_quantity' => 20,
            'stock_unit' => 'packs',
            'reorder_threshold' => 5,
            'status' => 'active',
        ]);
    }
}
