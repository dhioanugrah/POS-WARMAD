<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'location_id',
        'type',
        'qty_change',
        'qty_after',
        'unit_cost',
        'reference_type',
        'reference_id',
        'note',
        'user_id',
        'occurred_at',
    ];

    protected $casts = [
        'type' => StockMovementType::class,
        'qty_change' => 'decimal:3',
        'qty_after' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
