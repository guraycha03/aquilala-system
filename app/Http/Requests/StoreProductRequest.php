<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Basic Information
            'name'              => ['required', 'string', 'max:255'],
            'brand'             => ['nullable', 'string', 'max:255'],
            'sku'               => ['required', 'string', 'max:100', 'unique:products,sku'],
            'category_id'       => ['required', 'integer', 'exists:categories,id'],
            'product_type'      => ['nullable', 'string', 'max:255'],
            'description'       => ['nullable', 'string', 'max:5000'],

            // Packaging & Pricing
            'package_size'      => ['nullable', 'numeric', 'min:0'],
            'package_unit'      => ['nullable', 'string', 'max:50'],
            'packaging_type'    => ['nullable', 'string', 'max:100'],
            'price'             => ['required', 'numeric', 'min:0'],
            'price_unit'        => ['nullable', 'string', 'max:100'],

            // Inventory
            'stock_quantity'    => ['required', 'integer', 'min:0'],
            'stock_unit'        => ['nullable', 'string', 'max:50'],
            'reorder_threshold' => ['required', 'integer', 'min:0'],
            'recommended_season'=> ['required', Rule::in(['wet', 'dry', 'both'])],
            'status'            => ['required', Rule::in(['active', 'inactive', 'out_of_stock', 'discontinued'])],

            // Fertilizer-specific (conditional)
            'nitrogen_percentage'      => ['nullable', 'numeric', 'between:0,100'],
            'phosphorus_percentage'    => ['nullable', 'numeric', 'between:0,100'],
            'potassium_percentage'     => ['nullable', 'numeric', 'between:0,100'],
            'form_type'                => ['nullable', 'string', 'max:100'],
            'fertilizer_rate_min'      => ['nullable', 'numeric', 'min:0'],
            'fertilizer_rate_max'      => ['nullable', 'numeric', 'min:0'],
            'fertilizer_rate_unit'     => ['nullable', 'string', 'max:50'],
            'fertilizer_method'        => ['nullable', 'string', 'max:255'],
            'fertilizer_timing'        => ['nullable', 'string', 'max:255'],

            // Registration
            'registration_status'      => ['nullable', 'string', 'max:50'],
            'registration_authority'   => ['nullable', 'string', 'max:255'],
            'registration_number'      => ['nullable', 'string', 'max:255'],
            'lot_number'               => ['nullable', 'string', 'max:255'],
            'batch_number'             => ['nullable', 'string', 'max:255'],
            'manufacturing_date'       => ['nullable', 'date'],
            'expiration_date'          => ['nullable', 'date', 'after_or_equal:manufacturing_date'],
        ];
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
        return [
            'name.required' => 'The product name is required.',
            'sku.unique' => 'This SKU is already in use by another product.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid.',
            'price.required' => 'The product price is required.',
            'price.min' => 'The price cannot be negative.',
            'stock_quantity.required' => 'The stock quantity is required.',
            'stock_quantity.min' => 'The stock quantity cannot be negative.',
            'reorder_threshold.required' => 'The reorder threshold is required.',
            'recommended_season.required' => 'Please select a recommended season.',
            'nitrogen_percentage.between' => 'Nitrogen percentage must be between 0 and 100.',
            'phosphorus_percentage.between' => 'Phosphorus percentage must be between 0 and 100.',
            'potassium_percentage.between' => 'Potassium percentage must be between 0 and 100.',
            'expiration_date.after_or_equal' => 'The expiration date cannot be before the manufacturing date.',
        ];
    }
}
