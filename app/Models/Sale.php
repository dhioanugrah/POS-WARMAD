<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
        'location_id',
        'user_id',
        'shift_id',
        'customer_id',
        'sold_at',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_amount',
        'rounding_amount',
        'total',
        'paid_amount',
        'change_amount',
        'payment_status',
        'due_date',
        'status',
        'cost_total',
        'notes',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'sold_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_type' => DiscountType::class,
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'rounding_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'payment_status' => PaymentStatus::class,
        'due_date' => 'date',
        'status' => SaleStatus::class,
        'cost_total' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function isVoid(): bool
    {
        return $this->status === SaleStatus::Void;
    }
}
