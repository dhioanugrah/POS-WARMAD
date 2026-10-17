<?php

namespace App\Models;

use Database\Factories\StockOpnameItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    /** @use HasFactory<StockOpnameItemFactory> */
    use HasFactory;

    protected $fillable = [
        'stock_opname_id',
        'product_id',
        'system_qty',
        'counted_qty',
        'difference',
        'unit_cost',
    ];

    protected $casts = [
        'system_qty' => 'decimal:3',
        'counted_qty' => 'decimal:3',
        'difference' => 'decimal:3',
        'unit_cost' => 'decimal:2',
    ];

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
