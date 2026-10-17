<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Record a stock movement for a product at a location and update the
     * cached balance in `product_stocks`. This is the ONLY place allowed to
     * touch stock balances (Tech Spec §2 rule 1).
     *
     * Must be called from within a DB::transaction() by the caller when part
     * of a larger posting operation (checkout, purchase, opname, ...).
     */
    public function move(
        Product $product,
        int $locationId,
        float $qtyChange,
        StockMovementType $type,
        ?Model $reference = null,
        float $unitCost = 0,
        ?Carbon $occurredAt = null,
        ?string $note = null,
        ?int $userId = null,
    ): ?StockMovement {
        if ($product->type === ProductType::Service) {
            return null;
        }

        /** @var ProductStock $stock */
        $stock = ProductStock::query()
            ->where('product_id', $product->id)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            $stock = ProductStock::create([
                'product_id' => $product->id,
                'location_id' => $locationId,
                'qty' => 0,
            ]);
            $stock = ProductStock::query()
                ->where('product_id', $product->id)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();
        }

        $currentQty = (float) $stock->qty;

        if ($qtyChange < 0 && ! setting('pos.allow_negative_stock', true)) {
            $available = $currentQty;
            if ($available + $qtyChange < 0) {
                throw new InsufficientStockException($product->name, $available);
            }
        }

        $newQty = $currentQty + $qtyChange;
        $stock->update(['qty' => $newQty]);

        return StockMovement::create([
            'product_id' => $product->id,
            'location_id' => $locationId,
            'type' => $type,
            'qty_change' => $qtyChange,
            'qty_after' => $newQty,
            'unit_cost' => $unitCost,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'note' => $note,
            'user_id' => $userId ?? auth()->id(),
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    /**
     * Recalculate the product's moving-average cost when stock comes IN with
     * a known landed cost (purchase, opening balance), then record the
     * movement. Tech Spec §5.2.
     */
    public function applyIncomingCost(
        Product $product,
        int $locationId,
        float $qtyBase,
        float $landedUnitCost,
        StockMovementType $type,
        ?Model $reference = null,
        ?Carbon $occurredAt = null,
        ?string $note = null,
        ?int $userId = null,
    ): ?StockMovement {
        return DB::transaction(function () use ($product, $locationId, $qtyBase, $landedUnitCost, $type, $reference, $occurredAt, $note, $userId) {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);

            $totalQtyBefore = max(0, (float) ProductStock::query()
                ->where('product_id', $product->id)
                ->sum('qty'));

            $totalAfter = $totalQtyBefore + $qtyBase;

            $newAvg = $totalAfter <= 0
                ? $landedUnitCost
                : (($totalQtyBefore * (float) $product->avg_cost) + ($qtyBase * $landedUnitCost)) / $totalAfter;

            $product->update([
                'avg_cost' => round($newAvg, 2),
                'last_purchase_cost' => $landedUnitCost,
            ]);

            return $this->move(
                product: $product,
                locationId: $locationId,
                qtyChange: $qtyBase,
                type: $type,
                reference: $reference,
                unitCost: round($newAvg, 2),
                occurredAt: $occurredAt,
                note: $note,
                userId: $userId,
            );
        });
    }
}
