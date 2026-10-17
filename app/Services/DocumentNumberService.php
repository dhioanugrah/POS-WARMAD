<?php

namespace App\Services;

use App\Models\DocumentSequence;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    /**
     * Format: {PREFIX}-{KODE_LOKASI}-{YYMMDD}-{NNNN}, sequential per day per
     * location (Tech Spec §5.3). Must be called inside the caller's
     * DB::transaction() so the lock is held until the document is saved.
     */
    public function next(string $type, int $locationId): string
    {
        $location = Location::query()->findOrFail($locationId);
        $period = now()->format('ymd');
        $prefix = setting("doc.prefix.{$type}", strtoupper($type));

        $sequence = DocumentSequence::query()
            ->where('doc_type', $type)
            ->where('location_id', $locationId)
            ->where('period', $period)
            ->lockForUpdate()
            ->first();

        if (! $sequence) {
            $sequence = DocumentSequence::create([
                'doc_type' => $type,
                'location_id' => $locationId,
                'period' => $period,
                'last_number' => 0,
            ]);
            $sequence = DocumentSequence::query()
                ->where('doc_type', $type)
                ->where('location_id', $locationId)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();
        }

        $next = $sequence->last_number + 1;
        $sequence->update(['last_number' => $next]);

        return sprintf('%s-%s-%s-%04d', $prefix, $location->code, $period, $next);
    }
}
