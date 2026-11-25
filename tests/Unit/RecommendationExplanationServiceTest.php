<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\RecommendationExplanationService;
use PHPUnit\Framework\TestCase;

class RecommendationExplanationServiceTest extends TestCase
{
    public function test_it_explains_reasons_in_friendly_language(): void
    {
        $product = new Product();
        $product->id = 7;

        $service = new RecommendationExplanationService();

        $explanation = $service->explain(
            $product,
            ['crop_formula_match', 'season_fit'],
            [
                'crop' => 'Rice',
                'crop_label' => 'Rice',
                'kind' => 'seed',
                'kind_label' => 'seed',
                'season' => 'wet',
                'season_fit' => true,
            ],
        );

        $this->assertStringContainsString('Rice', $explanation);
        $this->assertStringContainsString('wet', $explanation);
        $this->assertStringNotContainsStringIgnoringCase('saved product', $explanation);
        $this->assertStringNotContainsStringIgnoringCase('system', $explanation);
        $this->assertMatchesRegularExpression('/\b(we|you|your)\b/i', $explanation);
        $this->assertDoesNotMatchRegularExpression('/\bAI\b/i', $explanation);
        $this->assertDoesNotMatchRegularExpression('/\bLLM\b/i', $explanation);
    }

    public function test_seed_explanation_describes_the_crop_and_planting_window(): void
    {
        $product = new Product();
        $product->id = 9;

        $explanation = (new RecommendationExplanationService())->explain(
            $product,
            ['planted_crop_match', 'season_fit', 'application_guidance'],
            [
                'crop' => 'Pechay',
                'crop_label' => 'Pechay (Bok Choy)',
                'kind' => 'seed',
                'kind_label' => 'seed',
                'season' => 'wet',
                'season_fit' => true,
                'application_method' => 'Seedling tray to field',
                'application_timing' => 'Wet season planting window',
            ],
        );

        $this->assertStringContainsString('Pechay', $explanation);
        $this->assertStringContainsString('wet', $explanation);
        $this->assertStringContainsString('planting window', $explanation);
    }

    public function test_explanations_vary_across_products(): void
    {
        $service = new RecommendationExplanationService();
        $reasons = ['planted_crop_match', 'season_fit', 'application_guidance'];
        $data = [
            'crop' => 'Pechay',
            'crop_label' => 'Pechay (Bok Choy)',
            'kind' => 'seed',
            'kind_label' => 'seed',
            'season' => 'wet',
            'season_fit' => true,
            'application_method' => 'Seedling tray to field',
            'application_timing' => 'Wet season planting window',
        ];

        $explanations = [];
        for ($id = 1; $id <= 15; $id++) {
            $product = new Product();
            $product->id = $id;
            $explanations[] = $service->explain($product, $reasons, $data);
        }

        $this->assertGreaterThanOrEqual(3, count(array_unique($explanations)));
    }

    public function test_explanation_is_stable_for_the_same_product_and_day(): void
    {
        $product = new Product();
        $product->id = 12;

        $service = new RecommendationExplanationService();
        $reasons = ['top_quoted', 'formula_backed'];
        $data = ['quotation_count' => 4];

        $first = $service->explain($product, $reasons, $data);
        $second = $service->explain($product, $reasons, $data);

        $this->assertSame($first, $second);
    }
}
