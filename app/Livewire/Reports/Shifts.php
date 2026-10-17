<?php

namespace App\Livewire\Reports;

use App\Livewire\Reports\Concerns\HasDateRangeFilter;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Shifts extends Component
{
    use HasDateRangeFilter, WithPagination;

    public function mount(): void
    {
        abort_if(auth()->user()->hasRole('Gudang'), 403);
        $this->mountDateRange();
    }

    public function render()
    {
        $shifts = Shift::query()
            ->with('user')
            ->whereBetween('opened_at', [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()])
            ->latest('opened_at')
            ->paginate(25);

        $service = app(ShiftService::class);
        $breakdowns = $shifts->getCollection()->mapWithKeys(fn ($shift) => [$shift->id => $service->paymentBreakdown($shift)]);

        return view('livewire.reports.shifts', [
            'shifts' => $shifts,
            'breakdowns' => $breakdowns,
        ]);
    }
}
