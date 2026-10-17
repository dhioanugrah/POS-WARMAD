<?php

namespace App\Livewire\Reports\Concerns;

trait HasDateRangeFilter
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public function mountDateRange(): void
    {
        $this->dateFrom = now()->format('Y-m-d');
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
}
