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

        return array_merge(ProductFields::rules($productId, $this->remainingImageSlots()), [
            'crop_types' => ['nullable', 'array', 'max:50'],
            'crop_types.*' => ['string', 'max:255'],
        ]);
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

            $cropLibrary = app(\App\Services\CropLibraryService::class);
            foreach ((array) $this->input('crop_types', []) as $index => $cropType) {
                if ($cropLibrary->forName($cropType) === null) {
                    $validator->errors()->add("crop_types.$index", 'Select a crop type from the available list.');
                }
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
