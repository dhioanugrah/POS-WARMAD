<?php

namespace App\Models;

use Database\Factories\ProductPriceTierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceTier extends Model
{
    /** @use HasFactory<ProductPriceTierFactory> */
    use HasFactory;

    protected $fillable = ['product_unit_id', 'min_qty', 'price'];

    protected $casts = [
        'min_qty' => 'decimal:3',
        'price' => 'decimal:2',
    ];

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }
}
