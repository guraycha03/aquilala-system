<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRegistration extends Model
{
    protected $fillable = [
        'product_id',
        'registration_status',
        'registration_authority',
        'registration_number',
        'lot_number',
        'batch_number',
        'manufacturing_date',
        'expiration_date',
    ];

    protected function casts(): array
    {
        return [
            'manufacturing_date' => 'date',
            'expiration_date' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
