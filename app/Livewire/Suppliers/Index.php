<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
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

    public string $contactPerson = '';

    public string $phone = '';

    public string $address = '';

    public string $notes = '';

    public bool $isActive = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'contactPerson', 'phone', 'address', 'notes']);
        $this->isActive = true;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $supplier = Supplier::query()->findOrFail($id);
        $this->editingId = $supplier->id;
        $this->name = $supplier->name;
        $this->contactPerson = (string) $supplier->contact_person;
        $this->phone = (string) $supplier->phone;
        $this->address = (string) $supplier->address;
        $this->notes = (string) $supplier->notes;
        $this->isActive = $supplier->is_active;
        $this->showModal = true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'contactPerson' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'contact_person' => $this->contactPerson ?: null,
            'phone' => $this->phone ?: null,
            'address' => $this->address ?: null,
            'notes' => $this->notes ?: null,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            Supplier::query()->findOrFail($this->editingId)->update($data);
        } else {
            $data['code'] = CodeGenerator::next(Supplier::class, 'SUP');
            Supplier::query()->create($data);
        }

        $this->showModal = false;
        $this->dispatch('notify', message: 'Supplier tersimpan.', type: 'success');
    }

    public function render()
    {
        return view('livewire.suppliers.index', [
            'suppliers' => Supplier::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(25),
        ]);
    }
}
