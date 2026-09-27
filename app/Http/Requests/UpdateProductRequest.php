<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Support\ProductFields;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product');

        return ProductFields::rules($productId, $this->remainingImageSlots());
    }

    /**
     * How many more images this product can take, counting what it already
     * holds. Never negative, so a full product rejects any further upload
     * instead of silently exceeding the limit.
     */
    private function remainingImageSlots(): int
    {
        $product = $this->route('product');

        if (! $product instanceof Product) {
            return Product::MAX_IMAGES;
        }

        return max(0, Product::MAX_IMAGES - $product->images()->count());
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $min = $this->input('fertilizer_rate_min');
            $max = $this->input('fertilizer_rate_max');
            if ($min !== null && $max !== null && $min > $max) {
                $validator->errors()->add('fertilizer_rate_max', 'The maximum application rate must be greater than or equal to the minimum rate.');
            }
        });
    }

    public function messages(): array
    {
        return ProductFields::messages(true);
    }

    public function attributes(): array
    {
        return ProductFields::attributes();
    }
}
