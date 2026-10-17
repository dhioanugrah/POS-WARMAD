<?php

namespace App\Livewire\Reports;

use App\Enums\CashTransactionType;
use App\Livewire\Reports\Concerns\HasDateRangeFilter;
use App\Models\CashTransaction;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Cash extends Component
{
    use HasDateRangeFilter;

    public function mount(): void
    {
        abort_if(auth()->user()->hasRole('Gudang'), 403);
        $this->mountDateRange();
    }

    public function render()
    {
        $transactions = CashTransaction::query()
            ->with('category')
            ->whereBetween('occurred_at', [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()])
            ->get();

        $byCategory = $transactions->groupBy(fn ($t) => $t->category->name)
            ->map(fn ($group, $name) => [
                'name' => $name,
                'type' => $group->first()->type->value,
                'total' => $group->sum('amount'),
            ])
            ->values();

        return view('livewire.reports.cash', [
            'byCategory' => $byCategory,
            'totalIn' => $transactions->where('type', CashTransactionType::In)->sum('amount'),
            'totalOut' => $transactions->where('type', CashTransactionType::Out)->sum('amount'),
        ]);
    }
}
