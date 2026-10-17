<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
        'supplier_id',
        'location_id',
        'user_id',
        'supplier_invoice_no',
        'purchased_at',
        'subtotal',
        'discount_amount',
        'other_cost',
        'tax_amount',
        'total',
        'paid_amount',
        'payment_status',
        'due_date',
        'status',
        'notes',
        'posted_at',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'payment_status' => PaymentStatus::class,
        'due_date' => 'date',
        'status' => PurchaseStatus::class,
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function isDraft(): bool
    {
        return $this->status === PurchaseStatus::Draft;
    }

    public function isPosted(): bool
    {
        return $this->status === PurchaseStatus::Posted;
    }
}
