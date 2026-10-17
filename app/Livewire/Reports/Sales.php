<?php

namespace App\Livewire\Reports;

use App\Enums\SaleStatus;
use App\Livewire\Reports\Concerns\HasDateRangeFilter;
use App\Models\Location;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Sales extends Component
{
    use HasDateRangeFilter;

    public string $locationFilter = '';

    public string $tab = 'harian';

    public function mount(): void
    {
        abort_if(auth()->user()->hasRole('Gudang'), 403);
        $this->mountDateRange();
    }

    private function baseQuery()
    {
        return Sale::query()
            ->where('status', SaleStatus::Completed)
            ->whereBetween('sold_at', [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()])
            ->when($this->locationFilter, fn ($q) => $q->where('location_id', $this->locationFilter));
    }

    public function render()
    {
        $sales = $this->baseQuery()->with(['cashier', 'customer', 'payments'])->get();

        $byDay = $sales->groupBy(fn ($s) => $s->sold_at->format('Y-m-d'))
            ->map(fn ($group, $day) => ['day' => $day, 'count' => $group->count(), 'total' => $group->sum('total')])
            ->values();

        $byCashier = $sales->groupBy(fn ($s) => $s->cashier->name)
            ->map(fn ($group, $name) => ['name' => $name, 'count' => $group->count(), 'total' => $group->sum('total')])
            ->values();

        $byMethod = $sales->flatMap->payments
            ->whereNull('voided_at')
            ->groupBy(fn ($p) => $p->method->value)
            ->map(fn ($group, $method) => ['method' => $method, 'count' => $group->count(), 'total' => $group->sum('amount')])
            ->values();

        $byCustomer = $sales->groupBy(fn ($s) => $s->customer->name ?? 'Umum')
            ->map(fn ($group, $name) => ['name' => $name, 'count' => $group->count(), 'total' => $group->sum('total')])
            ->sortByDesc('total')
            ->values();

        return view('livewire.reports.sales', [
            'sales' => $sales->sortByDesc('sold_at'),
            'byDay' => $byDay,
            'byCashier' => $byCashier,
            'byMethod' => $byMethod,
            'byCustomer' => $byCustomer,
            'total' => $sales->sum('total'),
            'count' => $sales->count(),
            'locations' => Location::query()->where('is_active', true)->pluck('name', 'id'),
        ]);
    }
}
