<?php

namespace App\Livewire\Products;

use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    public string $statusFilter = '';

    public bool $lowStockOnly = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $product = Product::query()->findOrFail($id);

        $hasTransactions = $product->movements()->exists()
            || SaleItem::query()->where('product_id', $id)->exists()
            || PurchaseItem::query()->where('product_id', $id)->exists();

        if ($hasTransactions) {
            $this->dispatch('notify', message: 'Barang sudah punya riwayat transaksi. Nonaktifkan saja, tidak bisa dihapus permanen.', type: 'error');

            return;
        }

        try {
            $product->units()->delete();
            $product->delete();
            $this->dispatch('notify', message: 'Barang dihapus.', type: 'success');
        } catch (QueryException) {
            $this->dispatch('notify', message: 'Barang tidak bisa dihapus.', type: 'error');
        }
    }

    public function toggleActive(int $id): void
    {
        $product = Product::query()->findOrFail($id);
        $product->update(['is_active' => ! $product->is_active]);
        $this->dispatch('notify', message: 'Status barang diperbarui.', type: 'success');
    }

    public function render()
    {
        $products = Product::query()
            ->with(['category', 'units' => fn ($q) => $q->orderByDesc('is_base')])
            ->withSum('stocks as total_stock', 'qty')
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
                ->orWhereHas('units', fn ($q3) => $q3->whereAnyBarcode($this->search))
            ))
            ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($this->lowStockOnly, fn ($q) => $q->whereRaw('(select coalesce(sum(qty),0) from product_stocks where product_stocks.product_id = products.id) <= products.min_stock'))
            ->orderBy('name')
            ->paginate(25);

        return view('livewire.products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
