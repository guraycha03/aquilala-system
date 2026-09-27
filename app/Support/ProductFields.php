<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Validation\Rule;

/**
 * Single source of truth for product field definitions.
 *
 * The manual create/edit forms and the spreadsheet importer both build their
 * validation from here, so an imported product can never be accepted by a rule
 * the manual form would reject (or vice versa).
 */
class ProductFields
{
    public const SEASONS = ['wet', 'dry', 'both'];

    public const STATUSES = ['active', 'inactive', 'out_of_stock', 'discontinued'];

    public const FERTILIZER_FIELDS = [
        'nitrogen_percentage',
        'phosphorus_percentage',
        'potassium_percentage',
        'form_type',
        'fertilizer_rate_min',
        'fertilizer_rate_max',
        'fertilizer_rate_unit',
        'fertilizer_method',
        'fertilizer_timing',
    ];

    public const REGISTRATION_FIELDS = [
        'registration_status',
        'registration_authority',
        'registration_number',
        'lot_number',
        'batch_number',
        'manufacturing_date',
        'expiration_date',
    ];

    /**
     * Every column an imported file may contain, in template order.
     *
     * `required` mirrors the "required" flags in the manual form, and `example`
     * is what the downloadable template pre-fills so the merchant can see the
     * expected shape without reading documentation.
     */
    public const COLUMNS = [
        'name' => [
            'label' => 'Product Name',
            'group' => 'Basic Information',
            'required' => true,
            'example' => 'Urea Fertilizer (46-0-0)',
        ],
        'brand' => [
            'label' => 'Brand',
            'group' => 'Basic Information',
            'required' => false,
            'example' => 'Philphos',
        ],
        'sku' => [
            'label' => 'SKU',
            'group' => 'Basic Information',
            'required' => true,
            'example' => 'OKR-UREA-46-50',
        ],
        'category_id' => [
            'label' => 'Category ID',
            'group' => 'Basic Information',
            'required' => false,
            'example' => '3',
        ],
        'category' => [
            'label' => 'Category Name',
            'group' => 'Basic Information',
            'required' => false,
            'example' => 'Nitrogen Fertilizer',
        ],
        'product_type' => [
            'label' => 'Product Type',
            'group' => 'Basic Information',
            'required' => false,
            'example' => 'Fertilizer',
        ],
        'description' => [
            'label' => 'Description',
            'group' => 'Basic Information',
            'required' => false,
            'example' => 'Prilled nitrogen fertilizer for basal application.',
        ],
        'package_size' => [
            'label' => 'Package Size',
            'group' => 'Packaging & Pricing',
            'required' => false,
            'example' => '50',
        ],
        'package_unit' => [
            'label' => 'Package Unit',
            'group' => 'Packaging & Pricing',
            'required' => false,
            'example' => 'kg',
        ],
        'packaging_type' => [
            'label' => 'Packaging Type',
            'group' => 'Packaging & Pricing',
            'required' => false,
            'example' => 'Sack',
        ],
        'price' => [
            'label' => 'Price',
            'group' => 'Packaging & Pricing',
            'required' => true,
            'example' => '2150.00',
        ],
        'price_unit' => [
            'label' => 'Price Unit',
            'group' => 'Packaging & Pricing',
            'required' => false,
            'example' => 'per sack',
        ],
        'stock_quantity' => [
            'label' => 'Stock Quantity',
            'group' => 'Inventory & Status',
            'required' => true,
            'example' => '120',
        ],
        'stock_unit' => [
            'label' => 'Stock Unit',
            'group' => 'Inventory & Status',
            'required' => false,
            'example' => 'sacks',
        ],
        'reorder_threshold' => [
            'label' => 'Reorder Threshold',
            'group' => 'Inventory & Status',
            'required' => true,
            'example' => '20',
        ],
        'recommended_season' => [
            'label' => 'Recommended Season',
            'group' => 'Inventory & Status',
            'required' => true,
            'example' => 'both',
        ],
        'status' => [
            'label' => 'Status',
            'group' => 'Inventory & Status',
            'required' => true,
            'example' => 'active',
        ],
        'nitrogen_percentage' => [
            'label' => 'Nitrogen %',
            'group' => 'Fertilizer Analysis',
            'required' => false,
            'example' => '46',
        ],
        'phosphorus_percentage' => [
            'label' => 'Phosphorus %',
            'group' => 'Fertilizer Analysis',
            'required' => false,
            'example' => '0',
        ],
        'potassium_percentage' => [
            'label' => 'Potassium %',
            'group' => 'Fertilizer Analysis',
            'required' => false,
            'example' => '0',
        ],
        'form_type' => [
            'label' => 'Form Type',
            'group' => 'Fertilizer Analysis',
            'required' => false,
            'example' => 'Prilled',
        ],
        'fertilizer_rate_min' => [
            'label' => 'Application Rate Min',
            'group' => 'Fertilizer Analysis',
            'required' => false,
            'example' => '25',
        ],
        'fertilizer_rate_max' => [
            'label' => 'Application Rate Max',
            'group' => 'Fertilizer Analysis',
            'required' => false,
            'example' => '50',
        ],
        'fertilizer_rate_unit' => [
            'label' => 'Application Rate Unit',
            'group' => 'Fertilizer Analysis',
            'required' => false,
            'example' => 'kg/ha',
        ],
        'fertilizer_method' => [
            'label' => 'Application Method',
            'group' => 'Fertilizer Analysis',
            'required' => false,
            'example' => 'Broadcasting at planting',
        ],
        'fertilizer_timing' => [
            'label' => 'Application Timing',
            'group' => 'Fertilizer Analysis',
            'required' => false,
            'example' => 'Basal and early vegetative growth',
        ],
        'registration_status' => [
            'label' => 'Registration Status',
            'group' => 'Registration & Batch',
            'required' => false,
            'example' => 'Registered',
        ],
        'registration_authority' => [
            'label' => 'Registration Authority',
            'group' => 'Registration & Batch',
            'required' => false,
            'example' => 'Fertilizer and Pesticide Authority',
        ],
        'registration_number' => [
            'label' => 'Registration Number',
            'group' => 'Registration & Batch',
            'required' => false,
            'example' => '1234-ABC-5678',
        ],
        'lot_number' => [
            'label' => 'Lot Number',
            'group' => 'Registration & Batch',
            'required' => false,
            'example' => 'LOT-2026-014',
        ],
        'batch_number' => [
            'label' => 'Batch Number',
            'group' => 'Registration & Batch',
            'required' => false,
            'example' => 'BATCH-0142',
        ],
        'manufacturing_date' => [
            'label' => 'Manufacturing Date',
            'group' => 'Registration & Batch',
            'required' => false,
            'example' => '2026-01-15',
        ],
        'expiration_date' => [
            'label' => 'Expiration Date',
            'group' => 'Registration & Batch',
            'required' => false,
            'example' => '2028-01-15',
        ],
    ];

    /**
     * Header spellings accepted from a merchant's own spreadsheet, already
     * normalised (lowercase, alphanumeric only) the same way `headerToField()`
     * normalises the real header, so a file exported from Excel or a supplier
     * catalogue matches without renaming columns.
     */
    public const HEADER_ALIASES = [
        'name' => ['name', 'productname', 'title', 'item', 'itemname', 'product'],
        'brand' => ['brand', 'manufacturer', 'make', 'brandname'],
        'sku' => ['sku', 'skucode', 'productcode', 'itemcode', 'code', 'barcode'],
        'category_id' => ['categoryid', 'catid', 'categorycode'],
        'category' => ['category', 'categoryname', 'subcategory', 'subcat', 'group', 'categorygroup'],
        'product_type' => ['producttype', 'type', 'kind', 'class', 'productclass'],
        'description' => ['description', 'desc', 'details', 'remarks', 'notes', 'info'],
        'package_size' => ['packagesize', 'size', 'netweight', 'weight', 'volume', 'qtyperpack'],
        'package_unit' => ['packageunit', 'unit', 'uom', 'measure', 'unitofmeasure'],
        'packaging_type' => ['packagingtype', 'packaging', 'pack', 'container'],
        'price' => ['price', 'sellingprice', 'unitprice', 'retailprice', 'srp', 'cost'],
        'price_unit' => ['priceunit', 'per', 'perunit', 'rateunit', 'pricingunit'],
        'stock_quantity' => ['stockquantity', 'stockqty', 'stock', 'quantity', 'qty', 'onhand', 'inventory', 'available'],
        'stock_unit' => ['stockunit', 'stockuom', 'inventoryunit'],
        'reorder_threshold' => ['reorderthreshold', 'reorder', 'minstock', 'minlevel', 'minimumstock', 'alertlevel', 'reorderlevel', 'criticallevel'],
        'recommended_season' => ['recommendedseason', 'season', 'plantingseason', 'applyseason'],
        'status' => ['status', 'state', 'availability'],
        'nitrogen_percentage' => ['nitrogenpercentage', 'nitrogen', 'n', 'npercentage', 'npk_n'],
        'phosphorus_percentage' => ['phosphoruspercentage', 'phosphorus', 'p', 'ppercentage', 'npk_p'],
        'potassium_percentage' => ['potassiumpercentage', 'potassium', 'k', 'kpercentage', 'npk_k'],
        'form_type' => ['formtype', 'form', 'fertilizerform', 'physicalform'],
        'fertilizer_rate_min' => ['fertilizerratemin', 'ratemin', 'applicationratemin', 'minrate', 'dosagemin', 'minapplication'],
        'fertilizer_rate_max' => ['fertilizerratemax', 'ratemax', 'applicationratemax', 'maxrate', 'dosagemax', 'maxapplication'],
        'fertilizer_rate_unit' => ['fertilizerrateunit', 'rateunit', 'applicationrateunit', 'dosageunit', 'perhectareunit'],
        'fertilizer_method' => ['fertilizermethod', 'method', 'applicationmethod', 'howtoapply'],
        'fertilizer_timing' => ['fertilizertiming', 'timing', 'applicationtiming', 'whentoapply'],
        'registration_status' => ['registrationstatus', 'regstatus', 'fstatus', 'pestregistrations'],
        'registration_authority' => ['registrationauthority', 'authority', 'regauthority', 'regbody'],
        'registration_number' => ['registrationnumber', 'regnumber', 'regno', 'fpn', 'fpanumber', 'permitnumber'],
        'lot_number' => ['lotnumber', 'lot', 'lotno', 'lotcode'],
        'batch_number' => ['batchnumber', 'batch', 'batchno', 'batchcode'],
        'manufacturing_date' => ['manufacturingdate', 'mfgdate', 'mfg', 'productiondate', 'manufacturedate'],
        'expiration_date' => ['expirationdate', 'expirydate', 'expdate', 'expiry', 'exp', 'bestbefore'],
    ];

    /**
     * Convenience headings that are not stored columns of their own.
     *
     * `npk` is expanded into the nitrogen, phosphorus and potassium percentage
     * columns during normalisation, so the review table shows and the merchant
     * edits the three percentages rather than a composite the database does not
     * store.
     */
    public const COMPOSITE_HEADERS = [
        'npk' => ['npk', 'npkgrade', 'npkformula', 'npkanalysis', 'grade'],
    ];

    /**
     * Image columns are recognised purely so the merchant gets told images are
     * not part of a file import instead of wondering why their column vanished.
     */
    public const IGNORED_IMAGE_HEADERS = [
        'image', 'images', 'imageurl', 'imagepath', 'photo', 'photos', 'photourl', 'picture', 'pictures', 'thumbnail',
    ];

    /**
     * Collapse the human group labels into the three toggles the review table
     * offers, so the merchant can show or hide the bulk of the grid.
     */
    public static function groupKey(string $group): string
    {
        return match ($group) {
            'Fertilizer Analysis' => 'fert',
            'Registration & Batch' => 'reg',
            default => 'core',
        };
    }

    /**
     * The three column groups the review table can toggle, in display order.
     *
     * @return array<string, string>
     */
    public static function toggleGroups(): array
    {
        return [
            'core' => 'Core columns (name, SKU, category, size, price, stock)',
            'fert' => 'Fertilizer analysis',
            'reg' => 'Registration & batch',
        ];
    }

    /**
     * How a field should be edited in the review table.
     *
     * Numbers stay as text inputs on purpose: a spreadsheet value of "2,150"
     * should be editable and fixable by hand rather than being blocked by the
     * browser's number input.
     */
    public static function inputType(string $field): string
    {
        if ($field === 'recommended_season' || $field === 'status') {
            return 'select';
        }

        if (in_array($field, ['manufacturing_date', 'expiration_date'], true)) {
            return 'date';
        }

        return 'text';
    }

    /**
     * Options for the two enumerated fields.
     *
     * @return array<string, string>
     */
    public static function optionsFor(string $field): array
    {
        if ($field === 'recommended_season') {
            return ['wet' => 'Wet', 'dry' => 'Dry', 'both' => 'Both'];
        }

        if ($field === 'status') {
            return [
                'active' => 'Active',
                'inactive' => 'Inactive',
                'out_of_stock' => 'Out of stock',
                'discontinued' => 'Discontinued',
            ];
        }

        return [];
    }

    /**
     * Validation rules for a product, identical for the manual forms and the
     * spreadsheet importer.
     *
     * @param  int|Product|null  $ignoreProduct  Product (or id) whose SKU is exempt from the unique check.
     * @param  int|null  $imageSlots  How many images may be attached in total; null skips the image rules.
     */
    public static function rules($ignoreProduct = null, ?int $imageSlots = null): array
    {
        $rules = [
            // Basic Information
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($ignoreProduct)],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'product_type' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],

            // Packaging & Pricing
            'package_size' => ['nullable', 'numeric', 'min:0'],
            'package_unit' => ['nullable', 'string', 'max:50'],
            'packaging_type' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'price_unit' => ['nullable', 'string', 'max:100'],

            // Inventory
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'stock_unit' => ['nullable', 'string', 'max:50'],
            'reorder_threshold' => ['required', 'integer', 'min:0'],
            'recommended_season' => ['required', Rule::in(self::SEASONS)],
            'status' => ['required', Rule::in(self::STATUSES)],

            // Fertilizer-specific
            'nitrogen_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'phosphorus_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'potassium_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'form_type' => ['nullable', 'string', 'max:100'],
            'fertilizer_rate_min' => ['nullable', 'numeric', 'min:0'],
            'fertilizer_rate_max' => ['nullable', 'numeric', 'min:0'],
            'fertilizer_rate_unit' => ['nullable', 'string', 'max:50'],
            'fertilizer_method' => ['nullable', 'string', 'max:255'],
            'fertilizer_timing' => ['nullable', 'string', 'max:255'],

            // Registration
            'registration_status' => ['nullable', 'string', 'max:50'],
            'registration_authority' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'lot_number' => ['nullable', 'string', 'max:255'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'manufacturing_date' => ['nullable', 'date'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:manufacturing_date'],
        ];

        if ($imageSlots !== null) {
            $rules['images'] = ['nullable', 'array', 'max:' . $imageSlots];
            $rules['images.*'] = ['image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'];
        }

        return $rules;
    }

    public static function messages(bool $isUpdate = false): array
    {
        $messages = [
            'name.required' => 'The product name is required.',
            'name.max' => 'The product name cannot be longer than 255 characters.',
            'sku.required' => 'The SKU is required.',
            'sku.unique' => 'This SKU is already in use by another product.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid.',
            'price.required' => 'The product price is required.',
            'price.numeric' => 'The price must be a number.',
            'price.min' => 'The price cannot be negative.',
            'stock_quantity.required' => 'The stock quantity is required.',
            'stock_quantity.integer' => 'The stock quantity must be a whole number.',
            'stock_quantity.min' => 'The stock quantity cannot be negative.',
            'reorder_threshold.required' => 'The reorder threshold is required.',
            'reorder_threshold.integer' => 'The reorder threshold must be a whole number.',
            'reorder_threshold.min' => 'The reorder threshold cannot be negative.',
            'recommended_season.required' => 'Please select a recommended season.',
            'recommended_season.in' => 'Recommended season must be one of: wet, dry, both.',
            'status.required' => 'Please select a status.',
            'status.in' => 'Status must be one of: active, inactive, out_of_stock, discontinued.',
            'package_size.numeric' => 'The package size must be a number.',
            'nitrogen_percentage.between' => 'Nitrogen percentage must be between 0 and 100.',
            'phosphorus_percentage.between' => 'Phosphorus percentage must be between 0 and 100.',
            'potassium_percentage.between' => 'Potassium percentage must be between 0 and 100.',
            'expiration_date.date' => 'The expiration date is not a valid date.',
            'expiration_date.after_or_equal' => 'The expiration date cannot be before the manufacturing date.',
        ];

        if ($isUpdate) {
            $messages['images.max'] = 'This product already has the maximum of ' . Product::MAX_IMAGES . ' images. Remove one before adding another.';
        } else {
            $messages['images.max'] = 'You can upload up to ' . Product::MAX_IMAGES . ' images per product.';
        }

        $messages['images.*.image'] = 'Each upload must be a valid image file.';
        $messages['images.*.mimes'] = 'Images must be in PNG, JPG, WebP or GIF format.';
        $messages['images.*.max'] = 'Each image must be 5MB or smaller.';

        return $messages;
    }

    /**
     * Friendly field labels so "The nitrogen_percentage field is required."
     * becomes "The nitrogen percentage is required."
     */
    public static function attributes(): array
    {
        $map = [
            'category_id' => 'category',
            'nitrogen_percentage' => 'nitrogen percentage',
            'phosphorus_percentage' => 'phosphorus percentage',
            'potassium_percentage' => 'potassium percentage',
            'fertilizer_rate_min' => 'minimum application rate',
            'fertilizer_rate_max' => 'maximum application rate',
            'fertilizer_rate_unit' => 'application rate unit',
            'fertilizer_method' => 'application method',
            'fertilizer_timing' => 'application timing',
            'registration_status' => 'registration status',
            'registration_authority' => 'registration authority',
            'registration_number' => 'registration number',
            'lot_number' => 'lot number',
            'batch_number' => 'batch number',
            'manufacturing_date' => 'manufacturing date',
            'expiration_date' => 'expiration date',
            'package_size' => 'package size',
            'package_unit' => 'package unit',
            'packaging_type' => 'packaging type',
            'price_unit' => 'price unit',
            'stock_quantity' => 'stock quantity',
            'stock_unit' => 'stock unit',
            'reorder_threshold' => 'reorder threshold',
            'recommended_season' => 'recommended season',
            'product_type' => 'product type',
        ];

        return $map;
    }
}
