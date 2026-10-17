<?php

namespace App\Models;

use Database\Factories\ProductUnitFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductUnit extends Model
{
    /** @use HasFactory<ProductUnitFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'unit_name',
        'conversion_factor',
        'is_base',
        'barcode',
        'price_sell',
        'is_default_sale',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:3',
        'is_base' => 'boolean',
        'price_sell' => 'decimal:2',
        'is_default_sale' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function priceTiers(): HasMany
    {
        return $this->hasMany(ProductPriceTier::class);
    }

    /** Extra barcodes beyond the primary `barcode` column — same unit, same price. */
    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }

    /** All barcodes (primary + extra) as a plain list of strings. */
    public function getAllBarcodesAttribute(): array
    {
        return collect([$this->barcode])
            ->merge($this->barcodes->pluck('barcode'))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Match a unit by ANY of its barcodes (primary or extra) — the single
     * place every scan-driven screen (POS, saldo awal, opname, ...) should
     * go through instead of querying the `barcode` column directly.
     */
    public function scopeWhereAnyBarcode(Builder $query, string $barcode): Builder
    {
        return $query->where(function (Builder $q) use ($barcode) {
            $q->where('barcode', $barcode)
                ->orWhereHas('barcodes', fn (Builder $q2) => $q2->where('barcode', $barcode));
        });
    }
}
