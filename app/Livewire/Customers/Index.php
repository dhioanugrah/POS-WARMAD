<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Support\CodeGenerator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $address = '';

    public $creditLimit = 0;

    public string $notes = '';

    public bool $isActive = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'phone', 'email', 'address', 'creditLimit', 'notes']);
        $this->isActive = true;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $customer = Customer::query()->findOrFail($id);
        $this->editingId = $customer->id;
        $this->name = $customer->name;
        $this->phone = (string) $customer->phone;
        $this->email = (string) $customer->email;
        $this->address = (string) $customer->address;
        $this->creditLimit = (string) $customer->credit_limit;
        $this->notes = (string) $customer->notes;
        $this->isActive = $customer->is_active;
        $this->showModal = true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
            'creditLimit' => 'required|numeric|min:0',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'address' => $this->address ?: null,
            'credit_limit' => $this->creditLimit,
            'notes' => $this->notes ?: null,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            Customer::query()->findOrFail($this->editingId)->update($data);
        } else {
            $data['code'] = CodeGenerator::next(Customer::class, 'PLG');
            Customer::query()->create($data);
        }

        $this->showModal = false;
        $this->dispatch('notify', message: 'Pelanggan tersimpan.', type: 'success');
    }

    public function render()
    {
        return view('livewire.customers.index', [
            'customers' => Customer::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(25),
        ]);
    }
}
