<?php

namespace App\Livewire;

use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();

        $baseQuery = fn ($date) => Sale::query()
            ->whereDate('sold_at', $date)
            ->where('status', SaleStatus::Completed);

        $todaySales = $baseQuery($today)->get();
        $yesterdayTotal = (float) $baseQuery($yesterday)->sum('total');

        $todayTotal = (float) $todaySales->sum('total');
        $todayCount = $todaySales->count();
        $grossProfit = (float) $todaySales->sum(fn ($sale) => $sale->total - $sale->cost_total);
        $avgPerTransaction = $todayCount > 0 ? $todayTotal / $todayCount : 0;
        $changePercent = $yesterdayTotal > 0 ? round((($todayTotal - $yesterdayTotal) / $yesterdayTotal) * 100, 1) : null;

        $shiftService = app(ShiftService::class);
        $cashInDrawer = (float) Shift::query()
            ->where('status', ShiftStatus::Open)
            ->get()
            ->sum(fn ($shift) => $shiftService->expectedCash($shift));

        $chartDays = collect(range(29, 0))->map(fn ($i) => $today->copy()->subDays($i));
        $salesByDay = Sale::query()
            ->where('status', SaleStatus::Completed)
            ->whereBetween('sold_at', [$chartDays->first()->startOfDay(), $today->copy()->endOfDay()])
            ->selectRaw('date(sold_at) as day, sum(total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $chartLabels = $chartDays->map(fn ($d) => $d->format('d/m'));
        $chartData = $chartDays->map(fn ($d) => (float) ($salesByDay[$d->format('Y-m-d')] ?? 0));

        $topProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->where('sales.sold_at', '>=', $today->copy()->subDays(6)->startOfDay())
            ->selectRaw('sale_items.product_name, sum(sale_items.qty_base) as qty, sum(sale_items.subtotal) as revenue')
            ->groupBy('sale_items.product_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $lowStockCount = Product::query()
            ->where('type', 'stock')
            ->where('is_active', true)
            ->withSum('stocks', 'qty')
            ->get()
            ->filter(fn ($p) => (float) ($p->stocks_sum_qty ?? 0) <= (float) $p->min_stock)
            ->count();

        return view('livewire.dashboard', [
            'todayTotal' => $todayTotal,
            'todayCount' => $todayCount,
            'grossProfit' => $grossProfit,
            'avgPerTransaction' => $avgPerTransaction,
            'changePercent' => $changePercent,
            'cashInDrawer' => $cashInDrawer,
            'chartLabels' => $chartLabels->all(),
            'chartData' => $chartData->all(),
            'topProducts' => $topProducts,
            'lowStockCount' => $lowStockCount,
        ]);
    }
}
