<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Support\ProductFields;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Rules live in ProductFields so the spreadsheet importer validates
        // against exactly the same definition as this form.
        return ProductFields::rules(null, Product::MAX_IMAGES);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate min rate <= max rate for fertilizer
            $min = $this->input('fertilizer_rate_min');
            $max = $this->input('fertilizer_rate_max');
            if ($min !== null && $max !== null && $min > $max) {
                $validator->errors()->add('fertilizer_rate_max', 'The maximum application rate must be greater than or equal to the minimum rate.');
            }
        });
    }

    public function messages(): array
    {
        return ProductFields::messages();
    }

    public function attributes(): array
    {
        return ProductFields::attributes();
    }
}
