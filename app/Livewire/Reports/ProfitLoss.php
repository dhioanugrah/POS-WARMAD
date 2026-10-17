<?php

namespace App\Livewire\Reports;

use App\Enums\CashTransactionType;
use App\Enums\SaleStatus;
use App\Livewire\Reports\Concerns\HasDateRangeFilter;
use App\Models\CashTransaction;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProfitLoss extends Component
{
    use HasDateRangeFilter;

    public function mount(): void
    {
        abort_if(auth()->user()->hasRole('Gudang'), 403);
        $this->mountDateRange();
    }

    public function render()
    {
        $range = [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()];

        $sales = Sale::query()->where('status', SaleStatus::Completed)->whereBetween('sold_at', $range)->get();
        $netSales = (float) $sales->sum('total');
        $cogs = (float) $sales->sum('cost_total');
        $grossProfit = $netSales - $cogs;

        $expenses = (float) CashTransaction::query()->where('type', CashTransactionType::Out)->whereBetween('occurred_at', $range)->sum('amount');
        $netProfit = $grossProfit - $expenses;

        return view('livewire.reports.profit-loss', [
            'netSales' => $netSales,
            'cogs' => $cogs,
            'grossProfit' => $grossProfit,
            'expenses' => $expenses,
            'netProfit' => $netProfit,
        ]);
    }
}
