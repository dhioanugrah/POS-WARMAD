<?php

namespace App\Livewire;

use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Models\Sale;
use App\Models\Shift;
use App\Services\ShiftService;
use Livewire\Component;

/**
 * Live "who's working right now" panel for the owner dashboard (RPT-01):
 * every open shift with its expected cash-in-drawer and running transaction
 * count, polling every few seconds so it stays current without a refresh.
 */
class ActiveShiftsMonitor extends Component
{
    public function render()
    {
        $shiftService = app(ShiftService::class);

        $shifts = Shift::query()
            ->where('status', ShiftStatus::Open)
            ->with(['user', 'location'])
            ->oldest('opened_at')
            ->get()
            ->map(function (Shift $shift) use ($shiftService) {
                $sales = Sale::query()->where('shift_id', $shift->id)->where('status', SaleStatus::Completed);

                return [
                    'shift' => $shift,
                    'expected_cash' => $shiftService->expectedCash($shift),
                    'sales_count' => (clone $sales)->count(),
                    'sales_total' => (float) (clone $sales)->sum('total'),
                    'last_sale_at' => (clone $sales)->max('sold_at'),
                ];
            });

        $recentSales = Sale::query()
            ->whereNotNull('shift_id')
            ->whereHas('shift', fn ($q) => $q->where('status', ShiftStatus::Open))
            ->where('status', SaleStatus::Completed)
            ->with('cashier')
            ->latest('sold_at')
            ->limit(8)
            ->get();

        return view('livewire.active-shifts-monitor', [
            'shifts' => $shifts,
            'recentSales' => $recentSales,
        ]);
    }
}
