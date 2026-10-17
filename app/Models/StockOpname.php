<?php

namespace App\Models;

use App\Enums\StockOpnameStatus;
use Database\Factories\StockOpnameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    /** @use HasFactory<StockOpnameFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
        'location_id',
        'status',
        'scope_category_id',
        'notes',
        'user_id',
        'posted_at',
        'posted_by',
    ];

    protected $casts = [
        'status' => StockOpnameStatus::class,
        'posted_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'scope_category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }
}
