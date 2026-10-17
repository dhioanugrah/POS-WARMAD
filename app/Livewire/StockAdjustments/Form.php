<?php

namespace App\Livewire\StockAdjustments;

use App\Enums\StockMovementType;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\DocumentNumberService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?int $locationId = null;

    public string $reason = 'damaged';

    public string $notes = '';

    public string $search = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public function mount(): void
    {
        $this->locationId = auth()->user()->default_location_id ?? Location::query()->where('is_active', true)->value('id');
    }

    public function addProduct(int $productId): void
    {
        $product = Product::with('units')->findOrFail($productId);
        $unit = $product->units->firstWhere('is_default_sale', true) ?? $product->units->first();
        if (! $unit) {
            return;
        }

        $this->items[] = [
            'product_id' => $product->id,
            'product_unit_id' => $unit->id,
            'product_name' => $product->name,
            'unit_name' => $unit->unit_name,
            'conversion_factor' => (float) $unit->conversion_factor,
            'qty' => '1',
        ];
        $this->search = '';
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(): void
    {
        $this->validate([
            'locationId' => 'required|exists:locations,id',
            'reason' => 'required|in:damaged,lost,expired,own_use,other',
            'items' => 'required|array|min:1',
            'items.*.qty' => 'required|numeric|min:0.001',
        ], [], ['items' => 'daftar barang']);

        DB::transaction(function () {
            $adjustment = StockAdjustment::create([
                'number' => app(DocumentNumberService::class)->next('adjustment', $this->locationId),
                'location_id' => $this->locationId,
                'reason' => $this->reason,
                'notes' => $this->notes,
                'user_id' => auth()->id(),
                'adjusted_at' => now(),
            ]);

            $stockService = app(StockService::class);

            foreach ($this->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $qtyBase = (float) $item['qty'] * (float) $item['conversion_factor'];

                $adjustment->items()->create([
                    'product_id' => $product->id,
                    'product_unit_id' => $item['product_unit_id'],
                    'qty' => $item['qty'],
                    'qty_base' => $qtyBase,
                    'unit_cost' => $product->avg_cost,
                ]);

                $stockService->move(
                    product: $product,
                    locationId: $this->locationId,
                    qtyChange: -$qtyBase,
                    type: StockMovementType::Adjustment,
                    reference: $adjustment,
                    unitCost: (float) $product->avg_cost,
                    note: ucfirst($this->reason),
                );
            }
        });

        $this->dispatch('notify', message: 'Penyesuaian stok tersimpan.', type: 'success');
        $this->redirect(route('stock-adjustments.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.stock-adjustments.form', [
            'locations' => Location::query()->where('is_active', true)->pluck('name', 'id'),
            'results' => $this->search
                ? Product::query()->where('is_active', true)->where('type', 'stock')
                    ->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
                    ->limit(10)->get()
                : collect(),
        ]);
    }
}
