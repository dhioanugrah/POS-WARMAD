<?php

namespace App\Livewire\StockOpnames;

use App\Enums\StockOpnameStatus;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Services\DocumentNumberService;
use App\Services\StockOpnameService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?int $opnameId = null;

    public ?StockOpnameStatus $status = null;

    public ?int $locationId = null;

    public ?int $scopeCategoryId = null;

    public string $notes = '';

    public string $scanInput = '';

    public function mount(?StockOpname $stockOpname = null): void
    {
        if ($stockOpname?->exists) {
            $this->opnameId = $stockOpname->id;
            $this->status = $stockOpname->status;
            $this->locationId = $stockOpname->location_id;
            $this->scopeCategoryId = $stockOpname->scope_category_id;
            $this->notes = (string) $stockOpname->notes;
        } else {
            $this->locationId = auth()->user()->default_location_id ?? Location::query()->where('is_active', true)->value('id');
        }
    }

    public function createSession(): void
    {
        $this->validate(['locationId' => 'required|exists:locations,id']);

        DB::transaction(function () {
            $opname = StockOpname::create([
                'number' => app(DocumentNumberService::class)->next('opname', $this->locationId),
                'location_id' => $this->locationId,
                'status' => StockOpnameStatus::Draft,
                'scope_category_id' => $this->scopeCategoryId,
                'notes' => $this->notes,
                'user_id' => auth()->id(),
            ]);

            $products = Product::query()
                ->where('type', 'stock')
                ->where('is_active', true)
                ->when($this->scopeCategoryId, fn ($q) => $q->where('category_id', $this->scopeCategoryId))
                ->get();

            foreach ($products as $product) {
                $qty = (float) $product->stocks()->where('location_id', $this->locationId)->value('qty');
                $opname->items()->create([
                    'product_id' => $product->id,
                    'system_qty' => $qty,
                    'unit_cost' => $product->avg_cost,
                ]);
            }

            $this->opnameId = $opname->id;
        });

        $this->redirect(route('stock-opnames.show', $this->opnameId), navigate: true);
    }

    public function updateCount(int $itemId, $value): void
    {
        StockOpnameItem::query()->where('id', $itemId)->update([
            'counted_qty' => $value === '' ? null : (float) $value,
        ]);
    }

    public function scan(): void
    {
        $value = trim($this->scanInput);
        $this->scanInput = '';
        if ($value === '') {
            return;
        }

        $product = Product::query()
            ->where(fn ($q) => $q->where('code', $value)->orWhereHas('units', fn ($q2) => $q2->whereAnyBarcode($value)))
            ->first();

        if (! $product) {
            return;
        }

        $item = StockOpnameItem::query()->where('stock_opname_id', $this->opnameId)->where('product_id', $product->id)->first();
        if (! $item) {
            return;
        }

        $item->update(['counted_qty' => (float) ($item->counted_qty ?? 0) + 1]);
    }

    public function post(): void
    {
        $opname = StockOpname::findOrFail($this->opnameId);
        app(StockOpnameService::class)->post($opname, auth()->user());
        $this->status = StockOpnameStatus::Posted;
        $this->dispatch('notify', message: 'Stok opname diposting.', type: 'success');
    }

    public function render()
    {
        return view('livewire.stock-opnames.form', [
            'locations' => Location::query()->where('is_active', true)->pluck('name', 'id'),
            'categories' => Category::query()->orderBy('name')->pluck('name', 'id'),
            'items' => $this->opnameId
                ? StockOpnameItem::query()->where('stock_opname_id', $this->opnameId)->with('product')->get()
                : collect(),
        ]);
    }
}
