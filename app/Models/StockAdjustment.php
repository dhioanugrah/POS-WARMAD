<?php

namespace App\Models;

use App\Enums\StockAdjustmentReason;
use Database\Factories\StockAdjustmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    /** @use HasFactory<StockAdjustmentFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
        'location_id',
        'reason',
        'notes',
        'user_id',
        'adjusted_at',
    ];

    protected $casts = [
        'reason' => StockAdjustmentReason::class,
        'adjusted_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }
}
