<?php

namespace App\Models;

use App\Enums\CashTransactionType;
use Database\Factories\CashTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    /** @use HasFactory<CashTransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
        'location_id',
        'user_id',
        'shift_id',
        'cash_category_id',
        'type',
        'method',
        'amount',
        'description',
        'occurred_at',
        'attachment_path',
    ];

    protected $casts = [
        'type' => CashTransactionType::class,
        'amount' => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CashCategory::class, 'cash_category_id');
    }
}
