<?php

namespace App\Models;

use Database\Factories\StockAdjustmentItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    /** @use HasFactory<StockAdjustmentItemFactory> */
    use HasFactory;

    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'product_unit_id',
        'qty',
        'qty_base',
        'unit_cost',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'qty_base' => 'decimal:3',
        'unit_cost' => 'decimal:2',
    ];

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
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
