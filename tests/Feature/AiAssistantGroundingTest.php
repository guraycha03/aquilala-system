<?php

namespace Tests\Feature;

use App\AI\AiIntent;
use App\AI\AiIntentRouter;
use App\AI\AiResponseValidator;
use App\Models\Category;
use App\Models\DecisionFormula;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The Aquilala assistant's grounding contract.
 *
 * Each test maps to one of the twelve scenarios the refactor was specified
 * against, plus the architecture guarantees underneath them. The theme
 * throughout: the model is adversarial, and Laravel is what the farmer can rely
 * on.
 *
 * `Http::fake` is used wherever a model is involved, so these assertions are
 * about this application's behaviour rather than about what a particular model
 * happens to say on a particular day.
 */
class AiAssistantGroundingTest extends TestCase
{
    use RefreshDatabase;

    private User $farmer;

    private User $store;

    private Product $urea;

    private Product $riceSeeds;

    protected function setUp(): void
    {
        parent::setUp();

        // No key by default: most of these scenarios assert the assistant is
        // correct with no provider at all.
        Config::set('services.groq.key', null);

        $this->farmer = $this->makeUser('farmer', 'juan_' . uniqid() . '@example.test');
        $this->store = $this->makeUser('store_owner', 'store_' . uniqid() . '@example.test');

        $fertilizer = $this->makeCategory('Fertilizers', 'fertilizers');
        $seeds = $this->makeCategory('Seeds', 'seeds');

        $this->urea = $this->makeProduct($this->store, $fertilizer, 'Premium Urea 46-0-0', 'UREA-46', 1450.00, 40);
        $this->riceSeeds = $this->makeProduct($this->store, $seeds, 'Inbred Rice Seeds NSIC Rc222', 'RICE-RC222', 1250.00, 60);

        // The approved rule matrix, for both seasons so a question that names a
        // season and one that does not both resolve. These rates are the
        // authority the assistant reports; nothing else may produce a quantity.
        foreach (['wet', 'dry'] as $season) {
            $this->makeFormula('Rice', $season, $this->urea, 3.00, 'Sacks');
            $this->makeFormula('Rice', $season, $this->riceSeeds, 2.00, 'Sacks');
        }
    }

    // ────────────────────────────────────────────────────────────
    //  1. Recommendations only ever name products that exist
    // ────────────────────────────────────────────────────────────

    public function test_fertilizer_request_only_returns_products_that_exist_in_the_database(): void
    {
        // A model that invents a brand Aquilala has never stocked, and a rate
        // the formulas do not support.
        $this->fakeModel([
            'type' => 'product_results',
            'answer' => 'For rice use **Urea** and also **Bio-Organic Booster XL** at 7 sacks per hectare.',
            'product_ids' => [$this->urea->id, 999999],
        ]);

        $response = $this->ask('What fertilizers for rice?');

        $response->assertOk();

        $ids = $this->suggestionIds($response);

        $this->assertTrue($ids->contains($this->urea->id));
        $this->assertTrue($ids->contains($this->riceSeeds->id));
        $this->assertNotContains(999999, $ids->all(), 'A product id the database does not hold must never reach the farmer.');
        $this->assertCount(2, $ids, 'Only formula-backed, active products for the crop.');

        // Every card is a real row, carrying the database's own numbers.
        foreach ($response->json('suggestions') as $card) {
            $product = Product::find($card['id']);

            $this->assertNotNull($product, 'Every suggested card must correspond to a real product.');
            $this->assertSame((float) $product->price, (float) $card['price_value']);
            $this->assertSame($product->display_name, $card['display_name']);
        }

        $this->assertStringNotContainsString('Bio-Organic Booster XL', $response->json('reply'));
        $this->assertStringNotContainsString('7 sacks', $response->json('reply'));
    }

    // ────────────────────────────────────────────────────────────
    //  2. A crop question is answered from the catalog
    // ────────────────────────────────────────────────────────────

    public function test_crop_question_is_answered_from_the_catalog(): void
    {
        $response = $this->ask('Do you have rice products?');

        $response->assertOk();

        $this->assertSame(AiIntent::PRODUCT_SEARCH, $response->json('intent'));

        $names = collect($response->json('suggestions'))->pluck('name');

        $this->assertTrue($names->contains('Premium Urea 46-0-0'));
        $this->assertTrue($names->contains('Inbred Rice Seeds NSIC Rc222'));
    }

    // ────────────────────────────────────────────────────────────
    //  3. "Can I review it?" resolves to the previous product
    // ────────────────────────────────────────────────────────────

    public function test_follow_up_resolves_the_previous_product(): void
    {
        $first = $this->ask('Do you have rice products?');
        $first->assertOk();

        $context = $first->json('context');

        $this->assertContains($this->urea->id, $context['last_product_ids']);
        $this->assertSame('Rice', $context['last_crop']);

        $second = $this->ask('Can I review it?', $context);
        $second->assertOk();

        $this->assertSame(AiIntent::PRODUCT_SEARCH, $second->json('intent'));
        $this->assertEqualsCanonicalizing(
            [$this->urea->id, $this->riceSeeds->id],
            $this->suggestionIds($second)->all()
        );

        // And the link is Laravel's, not prose the model improvised.
        $urls = collect($second->json('suggestions'))->pluck('url');

        $this->assertTrue($urls->contains(route('farmer.products.show', $this->urea->id)));

        foreach ($urls as $url) {
            $this->assertStringStartsWith(url('/'), $url, 'Product links come from the router, not the model.');
        }
    }

    // ────────────────────────────────────────────────────────────
    //  4. No matching product is a real answer
    // ────────────────────────────────────────────────────────────

    public function test_missing_crop_product_returns_a_controlled_no_results_message(): void
    {
        // The model tries to be helpful by inventing a substitute.
        $this->fakeModel([
            'type' => 'product_results',
            'answer' => 'We do not have mango products, but try our **Mango Booster Organic** instead.',
            'product_ids' => [],
        ]);

        $response = $this->ask('Do you have mango products?');

        $response->assertOk();

        $this->assertSame('no_results', $response->json('type'));
        $this->assertSame([], $response->json('suggestions'));
        $this->assertStringContainsString(
            "I couldn't find that product in the current Aquilala catalog for **Mango**",
            $response->json('reply')
        );
        $this->assertStringNotContainsString('Mango Booster Organic', $response->json('reply'));
    }

    // ────────────────────────────────────────────────────────────
    //  5. A price question is answered from the database
    // ────────────────────────────────────────────────────────────

    public function test_price_answer_comes_from_the_database(): void
    {
        $response = $this->ask('How much is the urea?');

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertSame(AiIntent::PRODUCT_SEARCH, $response->json('intent'));
        $this->assertStringContainsString('1,450.00', $reply);
        $this->assertSame(1450.0, (float) $this->cardFor($response, $this->urea->id)['price_value']);
    }

    // ────────────────────────────────────────────────────────────
    //  6. A product link is generated by Laravel
    // ────────────────────────────────────────────────────────────

    public function test_product_url_is_generated_by_laravel(): void
    {
        // A model that helpfully writes its own link.
        $this->fakeModel([
            'type' => 'product_results',
            'answer' => 'You can view it at https://aquilala.example.com/products/'.$this->urea->id
                .' or /farmer/products/'.$this->urea->id,
            'product_ids' => [$this->urea->id],
        ]);

        $response = $this->ask('Where can I see the urea?');

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertStringNotContainsString('aquilala.example.com', $reply);
        $this->assertStringNotContainsString('/farmer/products/', $reply);

        $this->assertSame(
            route('farmer.products.show', $this->urea->id),
            $this->cardFor($response, $this->urea->id)['url']
        );
    }

    // ────────────────────────────────────────────────────────────
    //  7. An unknown product id is rejected by the validator
    // ────────────────────────────────────────────────────────────

    public function test_validator_rejects_an_unknown_product_id(): void
    {
        $result = app(AiResponseValidator::class)->validate([
            'type' => 'product_results',
            'answer' => 'Here you go.',
            'product_ids' => [$this->urea->id, 424242],
        ], [$this->cardForModel()], 'Deterministic fallback.');

        $this->assertContains(AiResponseValidator::VIOLATION_UNKNOWN_PRODUCT, $result['violations']);
        $this->assertSame([$this->urea->id], $result['product_ids']);
        $this->assertNotContains(424242, $result['product_ids']);
    }

    // ────────────────────────────────────────────────────────────
    //  8. A conflicting price loses to the database
    // ────────────────────────────────────────────────────────────

    public function test_database_price_wins_over_a_conflicting_model_price(): void
    {
        $this->fakeModel([
            'type' => 'product_results',
            'answer' => '**Premium Urea 46-0-0** is ₱999.00 per sack, a great deal today.',
            'product_ids' => [$this->urea->id],
        ]);

        $response = $this->ask('How much is the urea?');

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertStringNotContainsString('999', $reply, 'The model price must be discarded, not displayed.');
        $this->assertStringContainsString('1,450.00', $reply);

        // The card the farmer clicks is priced from the database regardless.
        $this->assertSame(1450.0, (float) $this->cardFor($response, $this->urea->id)['price_value']);
    }

    // ────────────────────────────────────────────────────────────
    //  9. A quantity request without a farm area asks for the area
    // ────────────────────────────────────────────────────────────

    public function test_missing_farm_area_triggers_a_clarification_instead_of_a_guess(): void
    {
        // The model volunteers a figure for a farm it knows nothing about.
        $this->fakeModel([
            'type' => 'cost_estimate',
            'answer' => 'You will need about 9 sacks of urea for your rice.',
            'product_ids' => [],
        ]);

        $response = $this->ask('How much fertilizer should I use for rice in the wet season?');

        $response->assertOk();

        $this->assertSame('clarification', $response->json('type'));
        $this->assertStringContainsString('farm area in hectares', $response->json('reply'));
        $this->assertStringNotContainsString('9 sacks', $response->json('reply'));
        $this->assertStringNotContainsString('₱', $response->json('reply'));
        $this->assertSame([], $response->json('suggestions'));
    }

    public function test_a_cost_question_without_a_season_asks_for_the_season(): void
    {
        // #20 says to ask for what is missing. #38 Test 10 says "how much would
        // 2 hectares of corn cost?" must come back with a verified cost. The
        // assistant does both: it costs the planting for the season Laravel
        // says it is, discloses that it assumed it, and still asks.
        $response = $this->ask('How much would 2 hectares of rice cost?');

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertStringContainsString('wet season', $reply);
        $this->assertStringContainsString('dry season', $reply);

        // A real number from the engine, not a refusal.
        $this->assertStringContainsString('13,700.00', $reply);
        $this->assertStringContainsStringIgnoringCase('I used the current', $reply);
        $this->assertStringContainsStringIgnoringCase('recalculate', $reply);
    }

    // ────────────────────────────────────────────────────────────
    //  10. An unrelated question gets a scoped, concise answer
    // ────────────────────────────────────────────────────────────

    public function test_unrelated_question_gets_a_scoped_response(): void
    {
        $this->fakeModel([
            'type' => 'answer',
            'answer' => 'The weather in Manila tomorrow will be sunny with a high of 32 degrees.',
            'product_ids' => [],
        ]);

        $response = $this->ask('What is the weather in Manila tomorrow?');

        $response->assertOk();

        $this->assertSame(AiIntent::UNSUPPORTED, $response->json('intent'));
        $this->assertSame('unsupported', $response->json('type'));
        $this->assertStringContainsString('Aquilala', $response->json('reply'));
        $this->assertStringNotContainsString('32 degrees', $response->json('reply'));

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.groq.com'));
    }

    // ────────────────────────────────────────────────────────────
    //  11. A general farming question stays inside approved knowledge
    // ────────────────────────────────────────────────────────────

    public function test_general_farming_question_uses_approved_knowledge_only(): void
    {
        // The model reaches for a planting distance and a fertilizer rate that
        // the application's approved context does not contain.
        $this->fakeModel([
            'type' => 'answer',
            'answer' => 'Sow corn at 20cm apart and apply 4 bags of urea per hectare at 21 days.',
            'product_ids' => [],
        ]);

        $response = $this->ask('How do I plant corn?');

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertSame(AiIntent::FARMING_ADVICE, $response->json('intent'));

        // Maturity comes from the Bulan crop catalogue, the only crop reference
        // this assistant is allowed to quote.
        $this->assertStringContainsString('Corn', $reply);
        $this->assertStringContainsString('105', $reply);
        $this->assertStringNotContainsString('20cm', $reply);
        $this->assertStringNotContainsString('4 bags of urea', $reply);
        $this->assertStringNotContainsString('21 days', $reply);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.groq.com'));
    }

    // ────────────────────────────────────────────────────────────
    //  12. "Give me the best product" is answered by asking
    // ────────────────────────────────────────────────────────────

    public function test_vague_best_product_request_asks_which_crop(): void
    {
        $this->fakeModel([
            'type' => 'product_results',
            'answer' => 'The best product is **Premium Urea 46-0-0**, our top seller.',
            'product_ids' => [$this->urea->id],
        ]);

        $response = $this->ask('Give me the best product.');

        $response->assertOk();

        $this->assertSame(AiIntent::PRODUCT_RECOMMENDATION, $response->json('intent'));
        $this->assertSame('clarification', $response->json('type'));
        $this->assertStringContainsString('Which crop', $response->json('reply'));
        $this->assertSame([], $response->json('suggestions'), 'No crop means nothing may be recommended.');
    }

    // ────────────────────────────────────────────────────────────
    //  Architecture guarantees
    // ────────────────────────────────────────────────────────────

    public function test_a_cost_question_is_answered_by_the_rule_engine(): void
    {
        $response = $this->ask('How much would 2 hectares of rice cost in the wet season?');

        $response->assertOk();

        $this->assertSame(AiIntent::COST_ESTIMATION, $response->json('intent'));
        $this->assertSame('cost_estimate', $response->json('type'));

        $reply = $response->json('reply');

        // 3 sacks/ha and 2 sacks/ha over 2 ha, at the catalog's own prices.
        $this->assertStringContainsString('6 Sacks', $reply);
        $this->assertStringContainsString('4 Sacks', $reply);
        $this->assertStringContainsString(number_format((6 * 1450.00) + (4 * 1250.00), 2), $reply);

        // Soil and weather were never given, and the answer says so.
        $this->assertStringContainsString('clay loam', $reply);
        $this->assertStringContainsString('normal conditions', $reply);

        $this->assertEqualsCanonicalizing(
            [$this->urea->id, $this->riceSeeds->id],
            $this->suggestionIds($response)->all()
        );
    }

    public function test_the_model_cannot_restate_a_total_the_engine_did_not_produce(): void
    {
        $this->fakeModel([
            'type' => 'cost_estimate',
            'answer' => 'Two hectares of wet-season rice comes to about **₱9,999.00** in Aquilala.',
            'product_ids' => [],
        ]);

        $response = $this->ask('How much would 2 hectares of rice cost in the wet season?');

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertStringNotContainsString('9,999', $reply);
        $this->assertStringContainsString(number_format((6 * 1450.00) + (4 * 1250.00), 2), $reply);
    }

    public function test_the_model_may_keep_a_correct_explanation(): void
    {
        $this->fakeModel([
            'type' => 'cost_estimate',
            'answer' => 'That is **13,700.00** for two hectares of wet-season rice, using the Aquilala formulas.',
            'product_ids' => [],
        ]);

        $response = $this->ask('How much would 2 hectares of rice cost in the wet season?');

        $response->assertOk();

        // The model is allowed to be useful, as long as every number in its
        // wording is one Laravel already printed.
        $this->assertStringContainsString('13,700.00', $response->json('reply'));
    }

    public function test_intent_routing_is_decided_in_php_and_never_by_the_model(): void
    {
        $this->fakeModel([
            'type' => 'answer',
            'answer' => 'I would rather talk about something else entirely.',
            'product_ids' => [],
        ]);

        $router = app(AiIntentRouter::class);

        $this->assertSame(AiIntent::SYSTEM_HELP, $router->route('What can you do?')['intent']);
        $this->assertSame(AiIntent::PRODUCT_SEARCH, $router->route('Do you have urea products?')['intent']);
        $this->assertSame(AiIntent::PRODUCT_SEARCH, $router->route('How much is the urea?')['intent']);
        $this->assertSame(AiIntent::PRODUCT_RECOMMENDATION, $router->route('What fertilizers for rice?')['intent']);
        $this->assertSame(AiIntent::COST_ESTIMATION, $router->route('How much for 2 hectares of corn?')['intent']);
        $this->assertSame(AiIntent::FARMING_ADVICE, $router->route('How do I plant corn?')['intent']);
        $this->assertSame(AiIntent::UNSUPPORTED, $router->route('Who won the election?')['intent']);

        $slots = $router->route('How much would 2 hectares of rice cost?')['slots'];

        $this->assertSame('Rice', $slots['crop']);
        $this->assertSame(2.0, $slots['area']);
    }

    public function test_a_discontinued_product_is_never_offered(): void
    {
        $this->urea->update(['status' => 'discontinued']);

        $response = $this->ask('Do you have rice products?');

        $response->assertOk();

        $this->assertNotContains($this->urea->id, $this->suggestionIds($response)->all());
        $this->assertSame([$this->riceSeeds->id], $this->suggestionIds($response)->all());
    }

    public function test_conversation_context_is_persisted_with_the_saved_history(): void
    {
        $turn = $this->ask('Do you have rice products?');
        $turn->assertOk();

        $context = $turn->json('context');

        $save = $this->actingAs($this->farmer)->postJson(route('farmer.ai-chat.history.save'), [
            'messages' => [
                ['role' => 'user', 'content' => 'Do you have rice products?'],
                ['role' => 'assistant', 'content' => $turn->json('reply')],
            ],
            'context' => $context,
        ]);

        $save->assertOk();

        $show = $this->actingAs($this->farmer)
            ->getJson(route('farmer.ai-chat.history.show', $save->json('id')));

        $show->assertOk();

        $restored = $show->json('context');

        $this->assertSame($context['last_intent'], $restored['last_intent']);
        $this->assertSame($context['last_crop'], $restored['last_crop']);
        $this->assertEqualsCanonicalizing($context['last_product_ids'], $restored['last_product_ids']);
    }

    public function test_the_assistant_works_with_no_model_provider_configured(): void
    {
        Config::set('services.groq.key', null);

        $response = $this->ask('What fertilizers for rice?');

        $response->assertOk();

        $this->assertNotSame('', $response->json('reply'));
        $this->assertNotSame([], $response->json('suggestions'));

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.groq.com'));
    }

    public function test_a_failed_provider_returns_the_unavailable_message_instead_of_the_fallback_answer(): void
    {
        Config::set('services.groq.key', 'test-key');

        Http::fake([
            'api.groq.com/*' => Http::response(['error' => 'rate limited'], 429),
        ]);

        $response = $this->ask('Do you have rice products?');

        $response->assertOk();

        // When nobody could answer, the farmer gets one clear message to try
        // again — never a deterministic guess dressed up as the model's answer.
        $this->assertSame('unavailable', $response->json('source'));
        $this->assertStringContainsString('temporarily unavailable', $response->json('reply'));
        $this->assertStringNotContainsString('1,450.00', $response->json('reply'));
    }

    // ────────────────────────────────────────────────────────────
    //  12. A question that does not depend on a missing input is not asked
    //      for one
    //
    //  Found against the running application, not in the specification: both
    //  of these questions used to be read as a recommendation or a cost
    //  estimate, answered with "what is your farm area in hectares?", and then
    //  answered that way again on every following turn — the same reply,
    //  whatever was asked, because the reply never depended on the question.
    // ────────────────────────────────────────────────────────────

    public function test_an_availability_question_for_a_crop_is_not_answered_with_a_request_for_farm_area(): void
    {
        $response = $this->ask('Do you have any products for mango?');

        $response->assertOk();
        $this->assertSame('PRODUCT_SEARCH', $response->json('intent'));
        $this->assertStringNotContainsStringIgnoringCase('hectare', $response->json('reply'));
        $this->assertStringNotContainsStringIgnoringCase('farm area', $response->json('reply'));
    }

    public function test_a_price_question_naming_a_product_returns_the_price_even_after_a_crop_was_discussed(): void
    {
        // The conversation is about rice, so the router would otherwise inherit
        // that crop, read "how much" as a cost estimate, and ask for an area it
        // already had no need of.
        $response = $this->ask('How much is urea?', [
            'last_intent'      => 'PRODUCT_RECOMMENDATION',
            'last_crop'        => 'Rice',
            'last_season'      => 'wet',
            'last_area'        => null,
            'last_product_ids' => [],
        ]);

        $response->assertOk();
        $this->assertSame('PRODUCT_SEARCH', $response->json('intent'));
        $this->assertStringContainsString('1,450.00', $response->json('reply'));
        $this->assertStringNotContainsStringIgnoringCase('hectare', $response->json('reply'));
        $this->assertTrue($this->suggestionIds($response)->contains($this->urea->id));
    }

    // ────────────────────────────────────────────────────────────
    //  13. The remaining cases #40 lists by name that were not yet covered
    // ────────────────────────────────────────────────────────────

    public function test_an_unknown_sku_is_treated_as_an_unknown_product(): void
    {
        $response = $this->ask('Is SKU UREA-46 in stock?');

        $response->assertOk();

        $ids = $this->suggestionIds($response);

        $this->assertTrue(
            $ids->contains($this->urea->id) || $ids->isEmpty(),
            'A SKU either resolves to its own row or to nothing. It must never resolve to a different product.'
        );

        $this->assertFalse(
            $ids->contains($this->riceSeeds->id),
            'UREA-46 is not the rice seed product, so the seeds must not be offered for it.'
        );
    }

    public function test_an_out_of_stock_product_is_labelled_out_of_stock_and_never_as_available(): void
    {
        $this->urea->update(['stock_quantity' => 0]);

        $response = $this->ask('What fertilizers for rice?');

        $response->assertOk();

        $card = collect($response->json('suggestions'))->firstWhere('id', $this->urea->id);

        $this->assertNotNull($card, 'The row is real, so it stays in the list rather than vanishing.');

        // #8 and #9 treat availability as a state to state honestly, not a
        // reason to hide a product the formulas actually call for.
        $this->assertStringContainsStringIgnoringCase('out of stock', $card['stock']);
        $this->assertStringNotContainsStringIgnoringCase('in stock', $card['stock']);
        $this->assertStringNotContainsStringIgnoringCase('available', $card['stock']);
    }

    public function test_an_ambiguous_follow_up_asks_instead_of_guessing(): void
    {
        $response = $this->ask('How much is it?', [
            'last_intent'      => 'PRODUCT_SEARCH',
            'last_crop'        => 'Rice',
            'last_season'      => 'wet',
            'last_area'        => null,
            'last_product_ids' => [$this->urea->id, $this->riceSeeds->id],
        ]);

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertStringContainsStringIgnoringCase('do you mean', $reply);
        $this->assertStringContainsString('Premium Urea 46-0-0', $reply);
        $this->assertStringContainsString('Inbred Rice Seeds NSIC Rc222', $reply);
    }

    public function test_a_formula_the_application_does_not_hold_is_never_cited(): void
    {
        // Only Rice has a formula in the fixtures. The model cites Corn anyway.
        $this->fakeModel([
            'type' => 'recommendation',
            'answer' => 'Corn is covered by the Aquilala **wet-season formula**, so use 4 sacks per hectare.',
            'product_ids' => [$this->urea->id],
        ]);

        $response = $this->ask('What fertilizer should I use for rice?');

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertStringContainsStringIgnoringCase('rice', $reply);
        $this->assertStringNotContainsStringIgnoringCase('corn', $reply);
    }

    // ────────────────────────────────────────────────────────────
    //  14. #39, the misleading questions
    // ────────────────────────────────────────────────────────────

    public function test_a_price_the_user_remembers_wrongly_is_corrected_from_the_database(): void
    {
        $response = $this->ask('You told me earlier that product costs 1,500. Is that still the price?', [
            'last_intent'      => 'PRODUCT_SEARCH',
            'last_crop'        => null,
            'last_season'      => null,
            'last_area'        => null,
            'last_product_ids' => [$this->urea->id],
        ]);

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertStringContainsString('1,450.00', $reply);
        $this->assertStringNotContainsStringIgnoringCase(
            '1,500',
            $reply,
            'The remembered figure must not survive into the answer when the database disagrees.'
        );
    }

    public function test_a_yield_comparison_is_refused_as_a_comparison_but_still_answered(): void
    {
        // The catalogue holds a typical yield per crop, but nothing that ranks
        // one crop against another. Saying so is the honest answer.
        $response = $this->ask('Which one has the highest yield?');

        $response->assertOk();

        $reply = $response->json('reply');

        $this->assertStringContainsStringIgnoringCase('yield', $reply);
        $this->assertMatchesRegularExpression(
            '/(does\s+not|don\'t|cannot|no)\s+(currently\s+)?(have|has|rank|compare|rate)/i',
            $reply,
            'The assistant must say the comparison is not available rather than picking a winner.'
        );
    }

    // ────────────────────────────────────────────────────────────
    //  14. #38 Test 12 and #42 - a product that is not in the catalogue
    // ────────────────────────────────────────────────────────────

    public function test_a_named_product_that_does_not_exist_is_not_answered_with_a_shelf(): void
    {
        // "corn" is a crop with a full compatibility list, so the crop route
        // answers this with ten unrelated products. Handing a farmer a shelf
        // when they asked for one specific thing is the same failure as
        // inventing it.
        $response = $this->ask('Do you have a Super Corn Fertilizer 5000?');

        $response->assertOk();

        $this->assertSame('no_results', $response->json('type'));

        $reply = $response->json('reply');

        $this->assertStringContainsString(
            "I couldn't find that product in the current Aquilala catalog",
            $reply
        );
        $this->assertStringNotContainsString($this->urea->name, $reply);
        $this->assertStringNotContainsString($this->riceSeeds->name, $reply);
    }

    public function test_a_search_only_matches_on_the_words_that_identify_a_product(): void
    {
        // One shared word was enough to match anything. "dragon fruit
        // fertilizer" used to return a knapsack sprayer.
        $response = $this->ask('Do you have dragon fruit fertilizer?');

        $response->assertOk();

        $this->assertSame('no_results', $response->json('type'));
        $this->assertSame([], $response->json('suggestions'));
    }

    public function test_a_single_product_word_still_finds_the_product(): void
    {
        // The relevance rule must not empty ordinary single-word lookups.
        $response = $this->ask('Do you have urea?');

        $response->assertOk();

        $this->assertTrue(
            $this->suggestionIds($response)->contains($this->urea->id),
            'One word that names a product is a complete enough description of what the farmer wants.'
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

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function suggestionIds($response)
    {
        return collect($response->json('suggestions'))->pluck('id')->map(fn ($id) => (int) $id);
    }

    /**
     * @return array<string, mixed>
     */
    private function cardFor($response, int $productId): array
    {
        $card = collect($response->json('suggestions'))->firstWhere('id', $productId);

        $this->assertNotNull($card, "Expected a product card for #{$productId}.");

        return $card;
    }

    /**
     * A card in the exact shape the assistant hands to the validator.
     *
     * @return array<string, mixed>
     */
    private function cardForModel(): array
    {
        return [
            'id'           => $this->urea->id,
            'name'         => $this->urea->name,
            'display_name' => $this->urea->display_name,
            'brand'        => $this->urea->brand,
            'category'     => 'Fertilizers',
            'store'        => $this->store->name,
            'price'        => number_format((float) $this->urea->price, 2),
            'price_value'  => (float) $this->urea->price,
            'url'          => route('farmer.products.show', $this->urea->id),
        ];
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
