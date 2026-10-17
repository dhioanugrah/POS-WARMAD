<?php

namespace App\Livewire\Pos;

use App\Models\Customer;
use App\Support\CodeGenerator;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Embeddable search + quick-add customer picker (POS-07). The parent (Cashier)
 * listens for the `customer-selected` event to know which customer id was
 * chosen (null = "Umum").
 */
class CustomerPicker extends Component
{
    public bool $show = false;

    public string $search = '';

    public bool $showQuickAdd = false;

    public string $quickAddName = '';

    public string $quickAddPhone = '';

    public ?int $selectedCustomerId = null;

    public function open(): void
    {
        $this->show = true;
        $this->search = '';
    }

    public function select(?int $customerId): void
    {
        $this->selectedCustomerId = $customerId;
        $this->show = false;
        $this->dispatch('customer-selected', customerId: $customerId);
    }

    public function openQuickAdd(): void
    {
        $this->quickAddName = $this->search;
        $this->quickAddPhone = '';
        $this->showQuickAdd = true;
    }

    public function quickAdd(): void
    {
        $this->validate([
            'quickAddName' => 'required|string|max:150',
            'quickAddPhone' => 'nullable|string|max:30',
        ], [], ['quickAddName' => 'nama', 'quickAddPhone' => 'telepon']);

        $customer = Customer::query()->create([
            'code' => CodeGenerator::next(Customer::class, 'PLG'),
            'name' => $this->quickAddName,
            'phone' => $this->quickAddPhone ?: null,
            'is_active' => true,
        ]);

        $this->showQuickAdd = false;
        $this->select($customer->id);
    }

    #[On('cart-cleared')]
    public function resetSelection(): void
    {
        $this->selectedCustomerId = null;
    }

    public function render()
    {
        return view('livewire.pos.customer-picker', [
            'results' => $this->search
                ? Customer::query()
                    ->where('is_active', true)
                    ->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('phone', 'like', "%{$this->search}%"))
                    ->orderBy('name')
                    ->limit(10)
                    ->get()
                : collect(),
            'selectedCustomer' => $this->selectedCustomerId ? Customer::find($this->selectedCustomerId) : null,
        ]);
    }
}
