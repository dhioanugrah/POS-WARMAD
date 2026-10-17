<?php

namespace App\Livewire\Sales;

use App\Exceptions\PosException;
use App\Models\Sale;
use App\Services\SaleService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Sale $sale;

    public function mount(Sale $sale): void
    {
        abort_if(auth()->user()->hasRole('Kasir') && $sale->user_id !== auth()->id(), 403);

        $this->sale = $sale->load(['items', 'payments', 'customer', 'cashier', 'location', 'voidedBy']);
    }

    public function void(string $reason): void
    {
        if (! auth()->user()->can('sales.void')) {
            $this->dispatch('notify', message: 'Anda tidak memiliki izin untuk void.', type: 'error');

            return;
        }

        try {
            app(SaleService::class)->void($this->sale, $reason, auth()->user());
            $this->sale->refresh();
            $this->dispatch('notify', message: 'Transaksi di-void.', type: 'success');
        } catch (PosException $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.sales.show');
    }
}
