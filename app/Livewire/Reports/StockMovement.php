<?php

namespace App\Livewire\Reports;

use App\Livewire\Reports\Concerns\HasDateRangeFilter;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement as StockMovementModel;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class StockMovement extends Component
{
    use HasDateRangeFilter;

    public string $locationFilter = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function render()
    {
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to = Carbon::parse($this->dateTo)->endOfDay();

        $products = Product::query()->where('type', 'stock')->where('is_active', true)->orderBy('name')->get();

        $rows = $products->map(function ($product) use ($from, $to) {
            $base = StockMovementModel::query()
                ->where('product_id', $product->id)
                ->when($this->locationFilter, fn ($q) => $q->where('location_id', $this->locationFilter));

            $opening = (clone $base)->where('occurred_at', '<', $from)->sum('qty_change');
            $periodMovements = (clone $base)->whereBetween('occurred_at', [$from, $to])->get();
            $in = $periodMovements->where('qty_change', '>', 0)->sum('qty_change');
            $out = abs($periodMovements->where('qty_change', '<', 0)->sum('qty_change'));
            $closing = (float) $opening + (float) $in - (float) $out;

            return [
                'product' => $product,
                'opening' => (float) $opening,
                'in' => (float) $in,
                'out' => (float) $out,
                'closing' => $closing,
            ];
        })->filter(fn ($row) => $row['opening'] != 0 || $row['in'] != 0 || $row['out'] != 0)->values();

        return view('livewire.reports.stock-movement', [
            'rows' => $rows,
            'locations' => Location::query()->where('is_active', true)->pluck('name', 'id'),
        ]);
    }
}
