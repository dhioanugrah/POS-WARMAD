<?php

namespace App\Livewire\Purchases;

use App\Models\Purchase;
use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $supplierFilter = '';

    public string $statusFilter = '';

    public function render()
    {
        return view('livewire.purchases.index', [
            'purchases' => Purchase::query()
                ->with('supplier')
                ->when($this->supplierFilter, fn ($q) => $q->where('supplier_id', $this->supplierFilter))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->latest('purchased_at')
                ->paginate(25),
            'suppliers' => Supplier::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
