<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Enums\StockOpnameStatus;
use App\Exceptions\PosException;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockOpnameService
{
    public function __construct(private readonly StockService $stockService) {}

    /**
     * Post a draft opname: only items with a counted_qty are adjusted
     * (Tech Spec T-113 AC — items left blank are untouched).
     */
    public function post(StockOpname $opname, User $user): StockOpname
    {
        return DB::transaction(function () use ($opname, $user) {
            $opname = StockOpname::query()->lockForUpdate()->with('items.product')->findOrFail($opname->id);

            if ($opname->status !== StockOpnameStatus::Draft) {
                throw new PosException('Hanya opname berstatus Draft yang bisa diposting.');
            }

            foreach ($opname->items as $item) {
                if ($item->counted_qty === null) {
                    continue;
                }

                $currentQty = (float) $item->product->stocks()->where('location_id', $opname->location_id)->value('qty');
                $difference = round((float) $item->counted_qty - $currentQty, 3);

                $item->update([
                    'system_qty' => $currentQty,
                    'difference' => $difference,
                    'unit_cost' => $item->product->avg_cost,
                ]);

                if ($difference != 0) {
                    $this->stockService->move(
                        product: $item->product,
                        locationId: $opname->location_id,
                        qtyChange: $difference,
                        type: StockMovementType::Opname,
                        reference: $opname,
                        unitCost: (float) $item->product->avg_cost,
                        userId: $user->id,
                        note: "Opname {$opname->number}",
                    );
                }
            }

            $opname->update([
                'status' => StockOpnameStatus::Posted,
                'posted_at' => now(),
                'posted_by' => $user->id,
            ]);

            return $opname->fresh(['items.product']);
        });
    }
}
