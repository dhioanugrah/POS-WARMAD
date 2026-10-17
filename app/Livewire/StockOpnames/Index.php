<?php

namespace App\Livewire\StockOpnames;

use App\Models\StockOpname;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.stock-opnames.index', [
            'opnames' => StockOpname::query()->with('location')->latest()->paginate(25),
        ]);
    }
}
