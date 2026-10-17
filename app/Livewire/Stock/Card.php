<?php

namespace App\Livewire\Stock;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Card extends Component
{
    use WithPagination;

    public Product $product;

    public string $locationFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function render()
    {
        $openingBalance = StockMovement::query()
            ->where('product_id', $this->product->id)
            ->when($this->locationFilter, fn ($q) => $q->where('location_id', $this->locationFilter))
            ->where('occurred_at', '<', Carbon::parse($this->dateFrom)->startOfDay())
            ->sum('qty_change');

        $movements = StockMovement::query()
            ->where('product_id', $this->product->id)
            ->when($this->locationFilter, fn ($q) => $q->where('location_id', $this->locationFilter))
            ->whereBetween('occurred_at', [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()])
            ->with('reference')
            ->orderBy('occurred_at')
            ->paginate(50);

        return view('livewire.stock.card', [
            'movements' => $movements,
            'openingBalance' => (float) $openingBalance,
            'locations' => Location::query()->where('is_active', true)->pluck('name', 'id'),
        ]);
    }
}
