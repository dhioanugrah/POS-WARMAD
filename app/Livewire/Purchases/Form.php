<?php

namespace App\Livewire\Purchases;

use App\Enums\PurchaseStatus;
use App\Exceptions\PosException;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\DocumentNumberService;
use App\Services\PurchaseService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?int $purchaseId = null;

    public ?PurchaseStatus $status = null;

    public ?int $supplierId = null;

    public ?int $locationId = null;

    public string $supplierInvoiceNo = '';

    public string $purchasedAt = '';

    public $discountAmount = 0;

    public $otherCost = 0;

    public string $search = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public array $marginDrops = [];

    public function mount(?Purchase $purchase = null): void
    {
        if ($purchase?->exists) {
            $purchase->load('items.product');
            $this->purchaseId = $purchase->id;
            $this->status = $purchase->status;
            $this->supplierId = $purchase->supplier_id;
            $this->locationId = $purchase->location_id;
            $this->supplierInvoiceNo = (string) $purchase->supplier_invoice_no;
            $this->purchasedAt = $purchase->purchased_at->format('Y-m-d');
            $this->discountAmount = (string) $purchase->discount_amount;
            $this->otherCost = (string) $purchase->other_cost;

            $this->items = $purchase->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_unit_id' => $item->product_unit_id,
                'product_name' => $item->product->name,
                'unit_name' => $item->unit_name,
                'conversion_factor' => (float) $item->conversion_factor,
                'qty' => (string) $item->qty,
                'price' => (string) $item->price,
                'discount_amount' => (string) $item->discount_amount,
            ])->all();
        } else {
            $this->locationId = auth()->user()->default_location_id ?? Location::query()->where('is_active', true)->value('id');
            $this->purchasedAt = now()->format('Y-m-d');
            $this->status = PurchaseStatus::Draft;
        }
    }

    public function addProduct(int $productId): void
    {
        $product = Product::with('units')->findOrFail($productId);
        $unit = $product->units->firstWhere('is_default_sale', true) ?? $product->units->first();

        if (! $unit) {
            return;
        }

        $this->items[] = $this->makeItem($product, $unit);
        $this->search = '';
    }

    /**
     * Scan/type a barcode (or exact product code) and press Enter. The first
     * scan adds the row for the scanned unit, every repeat scan of that unit
     * bumps its qty by 1 — so incoming goods can be counted piece by piece.
     */
    public function scan(): void
    {
        $value = trim($this->search);
        $this->search = '';

        if ($value === '') {
            return;
        }

        $unit = ProductUnit::query()
            ->whereAnyBarcode($value)
            ->whereHas('product', fn ($q) => $q->where('is_active', true)->where('type', 'stock'))
            ->with('product.units')
            ->first();

        if (! $unit) {
            $product = Product::query()->where('code', $value)->where('is_active', true)->where('type', 'stock')->with('units')->first();
            $unit = $product?->units->firstWhere('is_default_sale', true) ?? $product?->units->first();
        }

        if (! $unit) {
            $this->dispatch('notify', message: 'Barang dengan barcode/kode tersebut tidak ditemukan.', type: 'error');
            $this->dispatch('focus-search');

            return;
        }

        $existingIndex = collect($this->items)->search(fn ($item) => $item['product_unit_id'] === $unit->id);

        if ($existingIndex !== false) {
            $this->items[$existingIndex]['qty'] = (string) ((float) ($this->items[$existingIndex]['qty'] ?: 0) + 1);
        } else {
            $this->items[] = $this->makeItem($unit->product, $unit);
        }

        $this->dispatch('focus-search');
    }

    private function makeItem(Product $product, ProductUnit $unit): array
    {
        return [
            'product_id' => $product->id,
            'product_unit_id' => $unit->id,
            'product_name' => $product->name,
            'unit_name' => $unit->unit_name,
            'conversion_factor' => (float) $unit->conversion_factor,
            'qty' => '1',
            'price' => (string) ((float) $product->last_purchase_cost * (float) $unit->conversion_factor ?: $unit->price_sell),
            'discount_amount' => '0',
            'available_units' => $product->units->map(fn ($u) => ['id' => $u->id, 'unit_name' => $u->unit_name, 'conversion_factor' => (float) $u->conversion_factor])->all(),
        ];
    }

    public function changeUnit(int $index, int $unitId): void
    {
        $unit = collect($this->items[$index]['available_units'] ?? [])->firstWhere('id', $unitId);
        if (! $unit) {
            return;
        }
        $this->items[$index]['product_unit_id'] = $unit['id'];
        $this->items[$index]['unit_name'] = $unit['unit_name'];
        $this->items[$index]['conversion_factor'] = $unit['conversion_factor'];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function getSubtotalProperty(): float
    {
        return collect($this->items)->sum(fn ($item) => ((float) $item['qty'] * (float) $item['price']) - (float) ($item['discount_amount'] ?: 0));
    }

    public function getTotalProperty(): float
    {
        return $this->subtotal - (float) ($this->discountAmount ?: 0) + (float) ($this->otherCost ?: 0);
    }

    public function saveDraft(): void
    {
        $this->validate([
            'supplierId' => 'required|exists:suppliers,id',
            'locationId' => 'required|exists:locations,id',
            'purchasedAt' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.price' => 'required|numeric|min:0',
        ], [], ['items' => 'daftar barang']);

        DB::transaction(function () {
            $subtotal = $this->subtotal;

            $data = [
                'supplier_id' => $this->supplierId,
                'location_id' => $this->locationId,
                'user_id' => auth()->id(),
                'supplier_invoice_no' => $this->supplierInvoiceNo ?: null,
                'purchased_at' => $this->purchasedAt,
                'subtotal' => $subtotal,
                'discount_amount' => (float) ($this->discountAmount ?: 0),
                'other_cost' => (float) ($this->otherCost ?: 0),
                'total' => $this->total,
                'payment_status' => 'paid',
                'status' => PurchaseStatus::Draft,
            ];

            if ($this->purchaseId) {
                $purchase = Purchase::query()->findOrFail($this->purchaseId);
                $purchase->update($data);
                $purchase->items()->delete();
            } else {
                $data['number'] = app(DocumentNumberService::class)->next('purchase', $this->locationId);
                $purchase = Purchase::create($data);
            }

            foreach ($this->items as $item) {
                $qtyBase = (float) $item['qty'] * (float) $item['conversion_factor'];
                $lineSubtotal = ((float) $item['qty'] * (float) $item['price']) - (float) ($item['discount_amount'] ?: 0);

                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'product_unit_id' => $item['product_unit_id'],
                    'unit_name' => $item['unit_name'],
                    'conversion_factor' => $item['conversion_factor'],
                    'qty' => $item['qty'],
                    'qty_base' => $qtyBase,
                    'price' => $item['price'],
                    'discount_amount' => $item['discount_amount'] ?: 0,
                    'subtotal' => $lineSubtotal,
                ]);
            }

            $this->purchaseId = $purchase->id;
        });

        $this->dispatch('notify', message: 'Draft pembelian tersimpan.', type: 'success');
        $this->redirect(route('purchases.show', $this->purchaseId), navigate: true);
    }

    public function post(): void
    {
        try {
            $result = app(PurchaseService::class)->post(Purchase::findOrFail($this->purchaseId), auth()->user());
            $this->status = $result['purchase']->status;
            $this->marginDrops = $result['marginDrops'];
            $this->dispatch('notify', message: 'Pembelian diposting. Stok bertambah.', type: 'success');
        } catch (PosException $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function applyPriceSuggestion(int $productUnitId, $newPrice): void
    {
        ProductUnit::query()->where('id', $productUnitId)->update(['price_sell' => (float) $newPrice]);
        $this->marginDrops = collect($this->marginDrops)->reject(fn ($d) => $d['product_unit_id'] === $productUnitId)->values()->all();
        $this->dispatch('notify', message: 'Harga jual diperbarui.', type: 'success');
    }

    public function void(string $reason): void
    {
        try {
            app(PurchaseService::class)->void(Purchase::findOrFail($this->purchaseId), $reason, auth()->user());
            $this->status = PurchaseStatus::Void;
            $this->dispatch('notify', message: 'Pembelian di-void.', type: 'success');
        } catch (PosException $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.purchases.form', [
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'locations' => Location::query()->where('is_active', true)->pluck('name', 'id'),
            'results' => $this->search
                ? Product::query()->where('is_active', true)->where('type', 'stock')
                    ->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
                    ->limit(10)->get()
                : collect(),
        ]);
    }
}
