<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $cashierFilter = '';

    public string $methodFilter = '';

    public string $statusFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfDay()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function setPreset(string $preset): void
    {
        [$this->dateFrom, $this->dateTo] = match ($preset) {
            'today' => [now()->format('Y-m-d'), now()->format('Y-m-d')],
            'yesterday' => [now()->subDay()->format('Y-m-d'), now()->subDay()->format('Y-m-d')],
            '7days' => [now()->subDays(6)->format('Y-m-d'), now()->format('Y-m-d')],
            'this_month' => [now()->startOfMonth()->format('Y-m-d'), now()->format('Y-m-d')],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d'), now()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d')],
            default => [$this->dateFrom, $this->dateTo],
        };
    }

    public function render()
    {
        $query = Sale::query()
            ->with(['cashier', 'customer'])
            ->when(auth()->user()->hasRole('Kasir'), fn ($q) => $q->where('user_id', auth()->id()))
            ->whereBetween('sold_at', [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()])
            ->when($this->search, fn ($q) => $q->where('number', 'like', "%{$this->search}%"))
            ->when($this->cashierFilter, fn ($q) => $q->where('user_id', $this->cashierFilter))
            ->when($this->methodFilter, fn ($q) => $q->whereHas('payments', fn ($q2) => $q2->where('method', $this->methodFilter)))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter));

        return view('livewire.sales.index', [
            'sales' => $query->latest('sold_at')->paginate(25),
            'cashiers' => User::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
