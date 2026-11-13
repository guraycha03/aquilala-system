<?php

namespace Tests\Feature;

use App\AI\AiIntent;
use App\AI\AquilalaAssistant;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * The store owner's side of the assistant, in the exact words the demo uses.
 *
 * The questions here are the ones the defense asks live: "Show my store
 * inventory", the low-stock chip, "do i have any product thats almost out of
 * stock", and "pano maglagay product sa store ko?". Each must come back as a
 * real answer read from this store's own rows with InventoryController's own
 * predicates — never a refusal, never another store's data, never a farmer's
 * pages.
 *
 * The last test covers the failure mode the demo must never show: when the
 * assistant itself throws, the chat gets one clean sentence, not a stack
 * trace and not a wall of offline notices.
 */
class AiStoreOwnerAssistantTest extends TestCase
{
    use RefreshDatabase;

    private User $store;

    private User $otherStore;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.groq.key', null);

        $this->store = $this->makeUser('store_owner', 'store_' . uniqid() . '@example.test');
        $this->otherStore = $this->makeUser('store_owner', 'other_' . uniqid() . '@example.test');

        $fertilizer = $this->makeCategory('Fertilizers', 'fertilizers');
        $seeds = $this->makeCategory('Seeds', 'seeds');

        // A healthy item, a low item, and an out-of-stock item — the three
        // states the Inventory page's metric cards count.
        $this->makeProduct($this->store, $fertilizer, 'Premium Urea 46-0-0', 'UREA-46', 1450.00, 40);
        $this->makeProduct($this->store, $fertilizer, 'Complete 14-14-14 Fertilizer', 'COMP-1414', 1120.00, 3);
        $this->makeProduct($this->store, $seeds, 'Hybrid Corn Seeds PR-63', 'CORN-PR63', 980.00, 0);

        // Another store's row, low on stock. It must never surface in this
        // store's answers — not in a count, not in a list.
        $this->makeProduct($this->otherStore, $fertilizer, 'Competitor Special Blend 20-20-20', 'COMP-X20', 1300.00, 2);
    }

    // ────────────────────────────────────────────────────────────
    //  The demo conversation, verbatim
    // ────────────────────────────────────────────────────────────

    public function test_show_my_store_inventory_is_answered_from_this_store_own_rows(): void
    {
        $response = $this->ask('Show my store inventory');

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertSame(AiIntent::SYSTEM_HELP, $response->json('intent'));
        $this->assertSame('answer', $response->json('type'));

        // InventoryController's own metric rules: low is quantity <= reorder
        // level (Complete at 3, Hybrid at 0), out is quantity <= 0 (Hybrid).
        $this->assertStringContainsString(
            'In your inventory: **3** produkto — **2** low stock, **1** out of stock.',
            $reply
        );
        $this->assertStringContainsString('- **Hybrid Corn Seeds PR-63** — stock: 0', $reply);
        $this->assertStringContainsString('- **Complete 14-14-14 Fertilizer** — stock: 3, reorder level: 5', $reply);
        $this->assertStringContainsString(route('store.inventory.index'), $reply);

        // The healthy item is not itemised, and no other store's row or
        // count leaks in.
        $this->assertStringNotContainsString('Premium Urea 46-0-0', $reply);
        $this->assertStringNotContainsString('Competitor Special Blend', $reply);
    }

    public function test_the_low_stock_chip_reports_the_page_own_low_stock_items(): void
    {
        $response = $this->ask('What products are low in stock?');

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertSame(AiIntent::SYSTEM_HELP, $response->json('intent'));
        $this->assertSame('answer', $response->json('type'));
        $this->assertStringContainsString('**2** low stock', $reply);
        $this->assertStringContainsString('Complete 14-14-14 Fertilizer', $reply);
        $this->assertStringNotContainsString('Competitor Special Blend', $reply);
    }

    public function test_almost_out_of_stock_reports_low_stock_honestly(): void
    {
        $response = $this->ask('do i have any product thats almost out of stock');

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertSame('answer', $response->json('type'));
        $this->assertStringContainsString('out of stock', $reply);
        $this->assertStringContainsString('**2** low stock', $reply);

        // The low-stock number includes the zero-stock row, exactly as the
        // Inventory page does, and the answer says so rather than let the
        // two figures look like a lie.
        $this->assertStringContainsString(
            'The Inventory page counts out-of-stock items inside its low-stock number too.',
            $reply
        );
        $this->assertStringContainsString(route('store.inventory.index'), $reply);
        $this->assertStringNotContainsString('Competitor Special Blend', $reply);
    }

    public function test_pano_maglagay_product_is_taught_in_taglish_with_the_store_page(): void
    {
        $response = $this->ask('pano maglagay product sa store ko?');

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertSame(AiIntent::SYSTEM_HELP, $response->json('intent'));
        $this->assertSame('answer', $response->json('type'));
        $this->assertStringContainsString('ganito po', $reply);
        $this->assertStringContainsString('**Add Product**', $reply);
        $this->assertStringContainsString('reorder level', $reply);
        $this->assertStringContainsString(route('store.storefront.index'), $reply);

        // A farmer's task must never be taught to a store account.
        $this->assertStringNotContainsString('Submit Harvest Report', $reply);
    }

    public function test_how_to_update_stock_points_at_the_inventory_page(): void
    {
        $response = $this->ask('How do I update stock?');

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertSame(AiIntent::SYSTEM_HELP, $response->json('intent'));
        $this->assertStringContainsString('**Update Stock**', $reply);
        $this->assertStringContainsString('reorder level', $reply);
        $this->assertStringContainsString(route('store.inventory.index'), $reply);
    }

    // ────────────────────────────────────────────────────────────
    //  Role boundaries
    // ────────────────────────────────────────────────────────────

    public function test_a_farmer_task_is_not_taught_to_a_store_owner(): void
    {
        $response = $this->ask('How do I submit a harvest report?');

        $response->assertOk();

        $reply = $response->json('reply');

        // Not a dead-end refusal: it names why the page is not here and
        // points at the pages this account does have.
        $this->assertStringContainsString('Harvest reports belong to farmer members', $reply);
        $this->assertStringContainsString(route('store.dashboard'), $reply);

        $this->assertStringNotContainsString('Submit Harvest Report', $reply);
        $this->assertStringNotContainsString(route('farmer.harvests.create'), $reply);
    }

    public function test_an_unsupported_question_is_refused_in_store_terms(): void
    {
        $response = $this->ask('Who won the election?');

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertSame('unsupported', $response->json('type'));
        $this->assertStringContainsString('store inventory', $reply);
        $this->assertStringNotContainsString('farming', $reply);
    }

    // ────────────────────────────────────────────────────────────
    //  Failure handling: one clean sentence, never a crash dump
    // ────────────────────────────────────────────────────────────

    public function test_a_crashing_assistant_returns_one_friendly_error(): void
    {
        $this->mock(AquilalaAssistant::class, function ($mock): void {
            $mock->shouldReceive('handle')->andThrow(new \RuntimeException('Groq connection refused'));
        });

        $response = $this->ask('Show my store inventory');

        $response->assertStatus(500);
        $response->assertJson([
            'error' => 'The AI failed to process your message. Please try again in a moment.',
        ]);

        $body = (string) $response->getContent();

        // The user sees one sentence they can act on — no exception class,
        // no provider detail, no stack frame.
        $this->assertStringNotContainsString('Groq connection refused', $body);
        $this->assertStringNotContainsString('RuntimeException', $body);
        $this->assertStringNotContainsString('#0', $body);
    }

    // ────────────────────────────────────────────────────────────
    //  Helpers
    // ────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>|null  $context
     */
    private function ask(string $message, ?array $context = null)
    {
        return $this->actingAs($this->store)->postJson(route('store.ai-chat.send'), [
            'messages' => [['role' => 'user', 'content' => $message]],
            'context' => $context,
        ]);
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create([
            'name'       => 'Green Valley Agri Supply',
            'first_name' => 'Green',
            'last_name'  => 'Valley',
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
