<?php

namespace Tests\Feature;

use App\AI\AiIntent;
use App\Models\Category;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * How the assistant talks, and what it answers.
 *
 * The grounding contract in AiAssistantGroundingTest is about what the
 * assistant is allowed to say. This file is about the other half: that it
 * says it, once, and that it answers the question that was asked.
 *
 * Three failures are guarded here, all of them things a store owner or a
 * farmer actually saw:
 *
 *   1. The assistant answered with a list of its own capabilities and
 *      limits instead of answering the question.
 *   2. The assistant repeated the same line over and over.
 *   3. A store owner asked about their own business and got a reply about
 *      the farmer's catalogue.
 */
class AiAssistantToneTest extends TestCase
{
    use RefreshDatabase;

    private User $store;

    private User $farmer;

    private Product $urea;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.groq.key', null);

        $this->store = $this->makeUser('store_owner', 'store_' . uniqid() . '@example.test');
        $this->farmer = $this->makeUser('farmer', 'farmer_' . uniqid() . '@example.test');

        $category = Category::create([
            'name' => 'Fertilizers',
            'slug' => 'fertilizers-' . uniqid(),
        ]);

        $this->urea = Product::create([
            'user_id'           => $this->store->id,
            'sku'               => 'UREA-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'name'              => 'Premium Urea 46-0-0',
            'category'          => $category->name,
            'price'             => 1450.00,
            'stock_quantity'    => 40,
            'reorder_threshold' => 5,
            'status'            => 'active',
        ]);

        $this->makeQuotation($this->urea, 2);
    }

    // ────────────────────────────────────────────────────────────
    //  1. A store owner's own question is answered from their data
    // ────────────────────────────────────────────────────────────

    public function test_a_store_owner_asking_about_trending_gets_their_own_quotations(): void
    {
        $response = $this->actingAs($this->store)->postJson(route('store.ai-chat.send'), [
            'messages' => [['role' => 'user', 'content' => "What's trending this week?"]],
        ]);

        $response->assertOk();

        $this->assertSame(AiIntent::STORE_DEMAND, $response->json('intent'));

        $reply = $response->json('reply');

        // The store's own product, and how often farmers quoted it.
        $this->assertStringContainsString('Premium Urea 46-0-0', $reply);
        $this->assertStringContainsString('2', $reply);

        // A demand answer is not a catalogue answer: the store's products
        // come back as cards so the reader can act on them.
        $this->assertNotEmpty($response->json('suggestions'));
    }

    public function test_a_store_owner_asking_what_to_restock_is_answered_from_their_quotations(): void
    {
        $response = $this->actingAs($this->store)->postJson(route('store.ai-chat.send'), [
            'messages' => [['role' => 'user', 'content' => 'Which products should I restock?']],
        ]);

        $response->assertOk();

        $this->assertSame(AiIntent::STORE_DEMAND, $response->json('intent'));
        $this->assertStringContainsString('Premium Urea 46-0-0', $response->json('reply'));
    }

    public function test_a_farmer_asking_about_trending_is_not_given_the_store_answer(): void
    {
        $response = $this->actingAs($this->farmer)->postJson(route('farmer.ai-chat.send'), [
            'messages' => [['role' => 'user', 'content' => "What's trending this week?"]],
        ]);

        $response->assertOk();

        $this->assertNotSame(AiIntent::STORE_DEMAND, $response->json('intent'));
    }

    // ────────────────────────────────────────────────────────────
    //  2. No capability lists, no lectures about the data
    // ────────────────────────────────────────────────────────────

    public function test_the_assistant_does_not_answer_with_a_list_of_its_own_limits(): void
    {
        $response = $this->actingAs($this->farmer)->postJson(route('farmer.ai-chat.send'), [
            'messages' => [['role' => 'user', 'content' => 'What can you do?']],
        ]);

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertStringNotContainsStringIgnoringCase('browse the web', $reply);
        $this->assertStringNotContainsStringIgnoringCase('I cannot', $reply);
        $this->assertStringNotContainsStringIgnoringCase('my capabilities', $reply);
        $this->assertStringNotContainsStringIgnoringCase('rule engine', $reply);
    }

    public function test_the_model_does_not_get_to_describe_where_its_numbers_came_from(): void
    {
        // A model that answers by explaining its own constraints.
        $this->fakeModel([
            'type' => 'answer',
            'answer' => 'I answer from the Aquilala database only. My capabilities are limited to the catalog. '
                . 'I cannot browse the web.',
            'product_ids' => [],
            'needs_clarification' => false,
            'clarification_question' => null,
        ]);

        $response = $this->actingAs($this->farmer)->postJson(route('farmer.ai-chat.send'), [
            'messages' => [['role' => 'user', 'content' => 'Do you have urea products?']],
        ]);

        $response->assertOk();

        // The constraints are Laravel's business, not the reader's.
        $this->assertStringNotContainsStringIgnoringCase('my capabilities', $response->json('reply'));
        $this->assertStringNotContainsStringIgnoringCase('I cannot browse', $response->json('reply'));
    }

    // ────────────────────────────────────────────────────────────
    //  3. The same line is not said twice
    // ────────────────────────────────────────────────────────────

    public function test_a_repeated_line_is_said_once(): void
    {
        $this->fakeModel([
            'type' => 'answer',
            'answer' => "### Urea\n\n- Urea is quoted most often by farmers.\n"
                . "- Urea is quoted most often by farmers.\n"
                . "- Urea is quoted most often by farmers.\n"
                . "- Keep urea on the shelf.\n",
            'product_ids' => [],
            'needs_clarification' => false,
            'clarification_question' => null,
        ]);

        $response = $this->actingAs($this->farmer)->postJson(route('farmer.ai-chat.send'), [
            'messages' => [['role' => 'user', 'content' => 'Do you have urea products?']],
        ]);

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertSame(
            1,
            substr_count($reply, 'Urea is quoted most often by farmers.'),
            'The repeated line should have been collapsed to one.'
        );
        $this->assertStringContainsString('Keep urea on the shelf.', $reply);
    }

    // ────────────────────────────────────────────────────────────
    //  4. Off-topic is rejected, in one line
    // ────────────────────────────────────────────────────────────

    public function test_an_off_topic_question_is_rejected_without_a_lecture(): void
    {
        $response = $this->actingAs($this->farmer)->postJson(route('farmer.ai-chat.send'), [
            'messages' => [['role' => 'user', 'content' => 'Who won the presidential election in 2024?']],
        ]);

        $response->assertOk();

        $this->assertSame('unsupported', $response->json('type'));
        $this->assertStringNotContainsStringIgnoringCase('browse the web', $response->json('reply'));
    }

    // ────────────────────────────────────────────────────────────
    //  Helpers
    // ────────────────────────────────────────────────────────────

    private function makeQuotation(Product $product, int $quantity): Quotation
    {
        $quotation = Quotation::create([
            'user_id'              => $this->farmer->id,
            'quotation_number'     => 'QT-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'crop_type'            => 'Rice',
            'season'               => 'wet',
            'land_area'            => 1.0,
            'estimated_total_cost' => 300.00,
            'status'               => Quotation::STATUS_COMPUTED,
            'valid_until'          => now()->addDays(14),
        ]);

        $quotation->items()->create([
            'product_id'          => $product->id,
            'product_name'        => $product->name,
            'price_at_generation' => 1450.00,
            'calculated_quantity' => $quantity,
            'subtotal'            => 1450.00 * $quantity,
        ]);

        return $quotation;
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function fakeModel(array $payload): void
    {
        Config::set('services.groq.key', 'test-key');

        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode($payload)],
                ]],
            ]),
        ]);
    }
}
