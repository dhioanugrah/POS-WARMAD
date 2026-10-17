<?php

namespace App\Livewire\Stock;

use App\Enums\PurchaseStatus;
use App\Models\Location;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Minimum extends Component
{
    public ?int $supplierId = null;

    private function lowStockProducts()
    {
        return Product::query()
            ->where('type', 'stock')
            ->where('is_active', true)
            ->withSum('stocks', 'qty')
            ->with('units')
            ->get()
            ->filter(fn ($p) => (float) ($p->stocks_sum_qty ?? 0) <= (float) $p->min_stock)
            ->map(function ($p) {
                $qty = (float) ($p->stocks_sum_qty ?? 0);
                $p->setAttribute('current_qty', $qty);
                $p->setAttribute('suggested_qty', max(0, ($p->min_stock * 2) - $qty));

                return $p;
            })
            ->values();
    }

    public function createDraftPurchase(): void
    {
        $this->validate(['supplierId' => 'required|exists:suppliers,id']);

        $locationId = auth()->user()->default_location_id ?? Location::query()->where('is_active', true)->value('id');
        $products = $this->lowStockProducts();

        $purchase = DB::transaction(function () use ($products, $locationId) {
            $purchase = Purchase::create([
                'number' => app(DocumentNumberService::class)->next('purchase', $locationId),
                'supplier_id' => $this->supplierId,
                'location_id' => $locationId,
                'user_id' => auth()->id(),
                'purchased_at' => now(),
                'subtotal' => 0,
                'total' => 0,
                'status' => PurchaseStatus::Draft,
            ]);

            foreach ($products as $product) {
                $baseUnit = $product->units->firstWhere('is_base', true) ?? $product->units->first();
                if (! $baseUnit) {
                    continue;
                }

                $price = (float) $product->last_purchase_cost ?: (float) $product->avg_cost;

                $purchase->items()->create([
                    'product_id' => $product->id,
                    'product_unit_id' => $baseUnit->id,
                    'unit_name' => $baseUnit->unit_name,
                    'conversion_factor' => 1,
                    'qty' => $product->suggested_qty,
                    'qty_base' => $product->suggested_qty,
                    'price' => $price,
                    'subtotal' => $price * $product->suggested_qty,
                ]);
            }

            $subtotal = (float) $purchase->items()->sum('subtotal');
            $purchase->update(['subtotal' => $subtotal, 'total' => $subtotal]);

            return $purchase;
        });

        $this->redirect(route('purchases.show', $purchase), navigate: true);
    }

    public function render()
    {
        return view('livewire.stock.minimum', [
            'products' => $this->lowStockProducts(),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
