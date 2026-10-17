<?php

namespace App\Models;

use Database\Factories\PurchaseItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    /** @use HasFactory<PurchaseItemFactory> */
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'product_unit_id',
        'unit_name',
        'conversion_factor',
        'qty',
        'qty_base',
        'price',
        'discount_amount',
        'subtotal',
        'landed_unit_cost',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:3',
        'qty' => 'decimal:3',
        'qty_base' => 'decimal:3',
        'price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'landed_unit_cost' => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
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
