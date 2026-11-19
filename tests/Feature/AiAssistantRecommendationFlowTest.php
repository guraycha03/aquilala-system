<?php

namespace Tests\Feature;

use App\AI\AiConversationContext;
use App\AI\AiIntent;
use App\AI\AiIntentRouter;
use App\Models\Category;
use App\Models\DecisionFormula;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * The multi-turn recommendation flow: the area question asked, the area
 * answer given, the season question asked, and the formula actually run.
 *
 * Regression for the failure reported against the running application:
 * "1ha" parsed as no area at all, fell through every question pattern to the
 * inherited-crop catch-all, and answered the farmer's own answer with a crop
 * fact sheet plus the "I do not give fertilizer rates" warning — the flow
 * never completed and the formulas were never run. No other test covered
 * the turn that answers the assistant's own slot question.
 */
class AiAssistantRecommendationFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $farmer;

    private User $store;

    private Product $urea;

    private Product $riceSeeds;

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
        $this->riceSeeds = $this->makeProduct($this->store, $seeds, 'Inbred Rice Seeds NSIC Rc222', 'RICE-RC222', 1250.00, 60);

        // The approved rule matrix, for both seasons so either answer to the
        // season question resolves.
        foreach (['wet', 'dry'] as $season) {
            $this->makeFormula('Rice', $season, $this->urea, 3.00, 'Sacks');
            $this->makeFormula('Rice', $season, $this->riceSeeds, 2.00, 'Sacks');
        }
    }

    // ────────────────────────────────────────────────────────────
    //  1. The reported conversation, replayed turn for turn
    // ────────────────────────────────────────────────────────────

    public function test_the_reported_conversation_completes_and_runs_the_formula(): void
    {
        $first = $this->ask('What fertilizers for rice?');

        $first->assertOk();
        $this->assertSame(AiIntent::PRODUCT_RECOMMENDATION, $first->json('intent'));
        $this->assertStringContainsString('farm area in hectares', $first->json('reply'));

        // "1ha" — no space, no unit word; just what a farmer types.
        $second = $this->ask('1ha', $first->json('context'));

        $second->assertOk();
        $this->assertSame(
            AiIntent::PRODUCT_RECOMMENDATION,
            $second->json('intent'),
            'The area answer must continue the recommendation flow, not fall through to crop advice.'
        );

        $secondReply = $second->json('reply');
        $this->assertStringContainsStringIgnoringCase('wet season', $secondReply);
        $this->assertStringContainsStringIgnoringCase('dry season', $secondReply);
        $this->assertStringNotContainsString(
            'Typical yield',
            $secondReply,
            'A crop fact sheet is the reply the farmer did not ask for.'
        );
        $this->assertStringNotContainsString(
            'I do not give fertilizer rates',
            $secondReply,
            'The warning that asks for the area must not appear after the area was given.'
        );
        $this->assertSame('clarification', $second->json('type'));
        $this->assertSame(1.0, (float) $second->json('context')['last_area']);

        // "wet" — the season, the last open slot.
        $third = $this->ask('wet', $second->json('context'));

        $third->assertOk();
        $this->assertSame(AiIntent::PRODUCT_RECOMMENDATION, $third->json('intent'));

        $reply = $third->json('reply');
        $this->assertStringContainsString(
            'Aquilala recommendation for Rice (wet season, 1 ha)',
            $reply,
            'The completed flow runs the formula and reports its own arithmetic.'
        );
        $this->assertStringContainsString('3 Sacks', $reply, 'The formula rate for urea on 1 ha.');
        $this->assertStringContainsString('2 Sacks', $reply, 'The formula rate for rice seeds on 1 ha.');
        $this->assertStringContainsString('6,850.00', $reply, '3 × 1,450 + 2 × 1,250 — the engine total.');
        $this->assertStringNotContainsString('Typical yield', $reply);
        $this->assertSame('cost_estimate', $third->json('type'));

        $ids = collect($third->json('suggestions'))->pluck('id');
        $this->assertTrue($ids->contains($this->urea->id));
        $this->assertTrue($ids->contains($this->riceSeeds->id));

        $context = $third->json('context');
        $this->assertSame('wet', $context['last_season']);
        $this->assertSame(1.0, (float) $context['last_area']);
        $this->assertSame(AiIntent::PRODUCT_RECOMMENDATION, $context['last_intent']);
    }

    // ────────────────────────────────────────────────────────────
    //  2. The parser: what a farmer actually types
    // ────────────────────────────────────────────────────────────

    public function test_a_compact_area_answer_is_parsed_as_hectares(): void
    {
        $router = app(AiIntentRouter::class);

        $this->assertSame(1.0, $router->route('1ha')['slots']['area']);
        $this->assertSame(1.5, $router->route('1.5ha')['slots']['area']);
        $this->assertSame(2.0, $router->route('2 ha')['slots']['area']);
        $this->assertSame(2.0, $router->route('2-hectare')['slots']['area']);
        $this->assertSame(3.0, $router->route('3 hectares')['slots']['area']);
    }

    // ────────────────────────────────────────────────────────────
    //  3. A bare slot answer resumes the flow that asked for it
    // ────────────────────────────────────────────────────────────

    public function test_a_bare_slot_answer_resumes_the_flow_that_asked(): void
    {
        $router = app(AiIntentRouter::class);
        $asking = new AiConversationContext(lastIntent: AiIntent::PRODUCT_RECOMMENDATION, lastCrop: 'Rice');

        $area = $router->route('1ha', $asking);
        $this->assertSame(AiIntent::PRODUCT_RECOMMENDATION, $area['intent']);
        $this->assertSame(1.0, $area['slots']['area']);

        $season = $router->route('wet', $asking);
        $this->assertSame(AiIntent::PRODUCT_RECOMMENDATION, $season['intent']);
        $this->assertSame('wet', $season['slots']['season']);

        $cost = $router->route(
            '2 ha',
            new AiConversationContext(lastIntent: AiIntent::COST_ESTIMATION, lastCrop: 'Rice')
        );
        $this->assertSame(AiIntent::COST_ESTIMATION, $cost['intent']);
        $this->assertSame(2.0, $cost['slots']['area']);
    }

    public function test_a_new_question_containing_an_area_is_not_swallowed_by_the_resume(): void
    {
        $router = app(AiIntentRouter::class);
        $asking = new AiConversationContext(lastIntent: AiIntent::PRODUCT_RECOMMENDATION, lastCrop: 'Rice');

        $routed = $router->route('How much would 1 ha cost?', $asking);

        $this->assertSame(
            AiIntent::COST_ESTIMATION,
            $routed['intent'],
            'A question containing an area is a new ask, not a slot answer.'
        );
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

    private function makeFormula(string $crop, string $season, Product $product, float $rate, string $unit): DecisionFormula
    {
        return DecisionFormula::create([
            'crop_type'        => $crop,
            'season'           => $season,
            'product_id'       => $product->id,
            'rate_per_hectare' => $rate,
            'unit'             => $unit,
        ]);
    }
}
