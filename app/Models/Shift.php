<?php

namespace App\Models;

use App\Enums\ShiftStatus;
use Database\Factories\ShiftFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    /** @use HasFactory<ShiftFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'location_id',
        'operator_name',
        'opened_at',
        'opening_cash',
        'closed_at',
        'counted_cash',
        'expected_cash',
        'difference',
        'notes',
        'status',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'closed_at' => 'datetime',
        'counted_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'difference' => 'decimal:2',
        'status' => ShiftStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function isOpen(): bool
    {
        return $this->status === ShiftStatus::Open;
    }
}
