<?php

namespace App\Models;

use Database\Factories\ProductBarcodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBarcode extends Model
{
    /** @use HasFactory<ProductBarcodeFactory> */
    use HasFactory;

    protected $fillable = ['product_unit_id', 'barcode'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }
}
