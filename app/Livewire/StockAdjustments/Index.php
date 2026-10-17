<?php

namespace App\Livewire\StockAdjustments;

use App\Models\StockAdjustment;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.stock-adjustments.index', [
            'adjustments' => StockAdjustment::query()->with(['location', 'items'])->latest()->paginate(25),
        ]);
    }
}
