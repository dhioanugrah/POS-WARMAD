<?php

namespace App\Models;

use App\Enums\CashTransactionType;
use Database\Factories\CashCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashCategory extends Model
{
    /** @use HasFactory<CashCategoryFactory> */
    use HasFactory;

    protected $fillable = ['name', 'type', 'is_active'];

    protected $casts = [
        'type' => CashTransactionType::class,
        'is_active' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }
}
