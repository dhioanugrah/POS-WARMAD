<?php

namespace App\Livewire\Stock;

use App\Enums\StockMovementType;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Opening extends Component
{
    public ?int $locationId = null;

    public string $search = '';

    /** @var array<int, array{product_id:int, product_name:string, unit_id:int, unit_name:string, conversion_factor:float, qty:mixed, unit_cost:mixed, units: array}> */
    public array $rows = [];

    public function mount(): void
    {
        $this->locationId = auth()->user()->default_location_id ?? Location::query()->where('is_active', true)->value('id');
    }

    /**
     * Add a product via the fuzzy search dropdown (defaults to its base
     * unit). Adding the same unit again just bumps its qty by 1, same as
     * re-scanning it.
     */
    public function addProduct(int $productId): void
    {
        $product = Product::with('units')->findOrFail($productId);
        $baseUnit = $product->units->firstWhere('is_base', true) ?? $product->units->first();

        if ($baseUnit) {
            $this->addUnitRow($baseUnit);
        }

        $this->search = '';
        $this->dispatch('focus-search');
    }

    /**
     * Scan/type a barcode (or exact product code) and press Enter — mirrors
     * the cashier screen: first scan adds the row (cost typed once), every
     * repeat scan of the same barcode just increments its qty by 1. This is
     * how you count loose physical stock without retyping cost each time.
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

        $this->addUnitRow($unit);
        $this->dispatch('focus-search');
    }

    private function addUnitRow(ProductUnit $unit): void
    {
        $existingIndex = collect($this->rows)->search(fn ($row) => $row['unit_id'] === $unit->id);

        if ($existingIndex !== false) {
            $this->rows[$existingIndex]['qty'] = (float) ($this->rows[$existingIndex]['qty'] ?: 0) + 1;

            return;
        }

        $product = $unit->product;

        $this->rows[] = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_id' => $unit->id,
            'unit_name' => $unit->unit_name,
            'conversion_factor' => (float) $unit->conversion_factor,
            'qty' => 1,
            'unit_cost' => '',
            'units' => $product->units->map(fn ($u) => [
                'id' => $u->id,
                'unit_name' => $u->unit_name,
                'conversion_factor' => (float) $u->conversion_factor,
            ])->all(),
        ];
    }

    public function changeUnit(int $index, int $unitId): void
    {
        $unit = collect($this->rows[$index]['units'])->firstWhere('id', $unitId);
        $this->rows[$index]['unit_id'] = $unit['id'];
        $this->rows[$index]['unit_name'] = $unit['unit_name'];
        $this->rows[$index]['conversion_factor'] = $unit['conversion_factor'];
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function save(): void
    {
        $this->validate([
            'locationId' => 'required|exists:locations,id',
            'rows' => 'required|array|min:1',
            'rows.*.qty' => 'required|numeric|min:0.001',
            'rows.*.unit_cost' => 'required|numeric|min:0',
        ], [], ['rows' => 'baris barang']);

        DB::transaction(function () {
            $service = app(StockService::class);

            foreach ($this->rows as $row) {
                $product = Product::findOrFail($row['product_id']);
                $qtyBase = (float) $row['qty'] * (float) $row['conversion_factor'];
                $landedUnitCost = (float) $row['unit_cost'] / (float) $row['conversion_factor'];

                $service->applyIncomingCost(
                    product: $product,
                    locationId: $this->locationId,
                    qtyBase: $qtyBase,
                    landedUnitCost: round($landedUnitCost, 2),
                    type: StockMovementType::Opening,
                    note: 'Saldo awal',
                );
            }
        });

        $this->rows = [];
        $this->dispatch('notify', message: 'Saldo awal tersimpan.', type: 'success');
    }

    public function render()
    {
        $existingProductIds = $this->locationId
            ? Product::query()
                ->whereHas('movements', fn ($q) => $q->where('type', StockMovementType::Opening)->where('location_id', $this->locationId))
                ->pluck('id')
            : collect();

        return view('livewire.stock.opening', [
            'locations' => Location::query()->where('is_active', true)->pluck('name', 'id'),
            'results' => $this->search
                ? Product::query()
                    ->where('is_active', true)
                    ->where('type', 'stock')
                    ->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
                        ->orWhereHas('units', fn ($q2) => $q2->whereAnyBarcode($this->search)))
                    ->limit(10)
                    ->get()
                : collect(),
            'existingProductIds' => $existingProductIds,
        ]);
    }
}
