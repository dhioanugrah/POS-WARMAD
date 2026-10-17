<?php

namespace App\Models;

use Database\Factories\ProductStockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends Model
{
    /** @use HasFactory<ProductStockFactory> */
    use HasFactory;

    protected $fillable = ['product_id', 'location_id', 'qty'];

    protected $casts = [
        'qty' => 'decimal:3',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
