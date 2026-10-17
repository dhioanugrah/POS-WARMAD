<?php

namespace App\Livewire\Reports;

use App\Enums\PurchaseStatus;
use App\Livewire\Reports\Concerns\HasDateRangeFilter;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Purchases extends Component
{
    use HasDateRangeFilter;

    public string $supplierFilter = '';

    public function mount(): void
    {
        abort_if(auth()->user()->hasRole('Gudang'), 403);
        $this->mountDateRange();
    }

    public function render()
    {
        $purchases = Purchase::query()
            ->with('supplier')
            ->where('status', PurchaseStatus::Posted)
            ->whereBetween('purchased_at', [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()])
            ->when($this->supplierFilter, fn ($q) => $q->where('supplier_id', $this->supplierFilter))
            ->latest('purchased_at')
            ->get();

        return view('livewire.reports.purchases', [
            'purchases' => $purchases,
            'total' => $purchases->sum('total'),
            'suppliers' => Supplier::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
