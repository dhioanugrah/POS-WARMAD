<?php

namespace App\Services;

use App\Enums\PurchaseStatus;
use App\Enums\StockMovementType;
use App\Exceptions\PosException;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(private readonly StockService $stockService) {}

    /**
     * Post a draft purchase: allocate discount/other-cost proportionally
     * into each item's landed cost, recompute the product's moving-average
     * cost, and move stock in (Tech Spec §5.1–5.2).
     *
     * @return array{purchase: Purchase, marginDrops: list<array{product_unit_id:int, product_name:string, unit_name:string, current_price:float, previous_cost:float, new_cost:float}>}
     */
    public function post(Purchase $purchase, User $user): array
    {
        return DB::transaction(function () use ($purchase, $user) {
            $purchase = Purchase::query()->lockForUpdate()->with('items.product.units')->findOrFail($purchase->id);

            if ($purchase->status !== PurchaseStatus::Draft) {
                throw new PosException('Hanya pembelian berstatus Draft yang bisa diposting.');
            }

            $itemSubtotalSum = (float) $purchase->items->sum('subtotal');
            $marginDrops = [];

            foreach ($purchase->items as $item) {
                $product = $item->product;
                $previousCost = (float) $product->avg_cost;

                $share = $itemSubtotalSum > 0 ? ((float) $item->subtotal / $itemSubtotalSum) : 0;
                $discountAlloc = (float) $purchase->discount_amount * $share;
                $costAlloc = (float) $purchase->other_cost * $share;

                $landedTotal = (float) $item->subtotal - $discountAlloc + $costAlloc;
                $landedUnitCost = $item->qty_base > 0 ? round($landedTotal / (float) $item->qty_base, 2) : 0;

                $item->update(['landed_unit_cost' => $landedUnitCost]);

                $this->stockService->applyIncomingCost(
                    product: $product,
                    locationId: $purchase->location_id,
                    qtyBase: (float) $item->qty_base,
                    landedUnitCost: $landedUnitCost,
                    type: StockMovementType::Purchase,
                    reference: $purchase,
                    occurredAt: $purchase->purchased_at,
                    userId: $user->id,
                );

                $newCost = (float) $product->fresh()->avg_cost;
                $baseUnit = $product->units->firstWhere('is_base', true);

                if ($baseUnit && (float) $baseUnit->price_sell > 0) {
                    $oldMargin = ((float) $baseUnit->price_sell - $previousCost) / (float) $baseUnit->price_sell;
                    $newMargin = ((float) $baseUnit->price_sell - $newCost) / (float) $baseUnit->price_sell;

                    if ($newMargin < $oldMargin && $newCost > $previousCost) {
                        $marginDrops[] = [
                            'product_unit_id' => $baseUnit->id,
                            'product_name' => $product->name,
                            'unit_name' => $baseUnit->unit_name,
                            'current_price' => (float) $baseUnit->price_sell,
                            'previous_cost' => $previousCost,
                            'new_cost' => $newCost,
                        ];
                    }
                }
            }

            $purchase->update(['status' => PurchaseStatus::Posted, 'posted_at' => now()]);

            return ['purchase' => $purchase->fresh(['items.product']), 'marginDrops' => $marginDrops];
        });
    }

    /**
     * Void a posted purchase: reverse stock (does NOT recompute historical
     * average cost — Tech Spec §5.2 explicitly keeps this simple).
     */
    public function void(Purchase $purchase, string $reason, User $user): Purchase
    {
        return DB::transaction(function () use ($purchase, $reason, $user) {
            $purchase = Purchase::query()->lockForUpdate()->with('items.product')->findOrFail($purchase->id);

            if ($purchase->status !== PurchaseStatus::Posted) {
                throw new PosException('Hanya pembelian berstatus Posted yang bisa di-void.');
            }

            foreach ($purchase->items as $item) {
                $this->stockService->move(
                    product: $item->product,
                    locationId: $purchase->location_id,
                    qtyChange: -(float) $item->qty_base,
                    type: StockMovementType::PurchaseVoid,
                    reference: $purchase,
                    unitCost: (float) $item->landed_unit_cost,
                    userId: $user->id,
                    note: "Void: {$reason}",
                );
            }

            $purchase->update([
                'status' => PurchaseStatus::Void,
                'voided_at' => now(),
                'voided_by' => $user->id,
                'void_reason' => $reason,
            ]);

            return $purchase;
        });
    }
}
