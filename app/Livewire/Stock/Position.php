<?php

namespace App\Livewire\Stock;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Position extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    public string $locationFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::query()
            ->where('type', 'stock')
            ->with(['category', 'units' => fn ($q) => $q->orderByDesc('is_base')])
            ->withSum(['stocks' => fn ($q) => $q->when($this->locationFilter, fn ($q2) => $q2->where('location_id', $this->locationFilter))], 'qty')
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%")))
            ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->orderBy('name')
            ->paginate(25);

        $canViewCost = auth()->user()->can('products.view-cost');
        $totalValue = null;

        if ($canViewCost) {
            $totalValue = Product::query()
                ->where('type', 'stock')
                ->withSum(['stocks' => fn ($q) => $q->when($this->locationFilter, fn ($q2) => $q2->where('location_id', $this->locationFilter))], 'qty')
                ->get()
                ->sum(fn ($p) => (float) ($p->stocks_sum_qty ?? 0) * (float) $p->avg_cost);
        }

        return view('livewire.stock.position', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->pluck('name', 'id'),
            'locations' => Location::query()->where('is_active', true)->pluck('name', 'id'),
            'canViewCost' => $canViewCost,
            'totalValue' => $totalValue,
        ]);
    }
}
