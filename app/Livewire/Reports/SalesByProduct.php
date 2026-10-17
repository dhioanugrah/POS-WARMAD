<?php

namespace App\Livewire\Reports;

use App\Enums\SaleStatus;
use App\Livewire\Reports\Concerns\HasDateRangeFilter;
use App\Models\SaleItem;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SalesByProduct extends Component
{
    use HasDateRangeFilter;

    public function mount(): void
    {
        abort_if(auth()->user()->hasRole('Gudang'), 403);
        $this->mountDateRange();
    }

    public function render()
    {
        $rows = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereBetween('sales.sold_at', [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()])
            ->selectRaw('sale_items.product_name, sum(sale_items.qty) as qty, sum(sale_items.subtotal) as revenue, sum(sale_items.unit_cost * sale_items.qty) as cost')
            ->groupBy('sale_items.product_name')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($row) {
                $row->profit = $row->revenue - $row->cost;
                $row->margin = $row->revenue > 0 ? round(($row->profit / $row->revenue) * 100, 1) : 0;

                return $row;
            });

        return view('livewire.reports.sales-by-product', [
            'rows' => $rows,
            'canViewCost' => auth()->user()->can('products.view-cost'),
        ]);
    }
}
