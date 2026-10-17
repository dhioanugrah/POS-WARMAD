<?php

namespace App\Models;

use App\Enums\DiscountType;
use Database\Factories\SaleItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    /** @use HasFactory<SaleItemFactory> */
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'product_unit_id',
        'product_name',
        'unit_name',
        'conversion_factor',
        'qty',
        'qty_base',
        'price',
        'discount_type',
        'discount_value',
        'discount_amount',
        'subtotal',
        'unit_cost',
        'price_overridden',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:3',
        'qty' => 'decimal:3',
        'qty_base' => 'decimal:3',
        'price' => 'decimal:2',
        'discount_type' => DiscountType::class,
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'price_overridden' => 'boolean',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }
}
