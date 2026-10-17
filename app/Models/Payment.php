<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'payable_type',
        'payable_id',
        'direction',
        'method',
        'amount',
        'reference',
        'paid_at',
        'user_id',
        'shift_id',
        'location_id',
        'is_initial',
        'voided_at',
    ];

    protected $casts = [
        'method' => PaymentMethod::class,
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'is_initial' => 'boolean',
        'voided_at' => 'datetime',
    ];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
