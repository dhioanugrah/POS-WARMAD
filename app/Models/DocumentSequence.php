<?php

namespace App\Models;

use Database\Factories\DocumentSequenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSequence extends Model
{
    /** @use HasFactory<DocumentSequenceFactory> */
    use HasFactory;

    protected $fillable = ['doc_type', 'location_id', 'period', 'last_number'];

    protected $casts = [
        'last_number' => 'integer',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
