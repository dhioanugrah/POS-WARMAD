<?php

namespace App\Livewire\Settings;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Locations extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $type = 'store';

    public string $address = '';

    public bool $isActive = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'code', 'name', 'type', 'address']);
        $this->isActive = true;
        $this->type = 'store';
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $location = Location::query()->findOrFail($id);
        $this->editingId = $location->id;
        $this->code = $location->code;
        $this->name = $location->name;
        $this->type = $location->type->value;
        $this->address = (string) $location->address;
        $this->isActive = $location->is_active;
        $this->showModal = true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:10|unique:locations,code,'.$this->editingId,
            'name' => 'required|string|max:150',
            'type' => 'required|in:store,warehouse',
            'address' => 'nullable|string|max:255',
        ];
    }

    public function save(): void
    {
        $this->validate();

        Location::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'code' => strtoupper($this->code),
                'name' => $this->name,
                'type' => $this->type,
                'address' => $this->address,
                'is_active' => $this->isActive,
            ]
        );

        $this->showModal = false;
        $this->dispatch('notify', message: 'Lokasi tersimpan.', type: 'success');
    }

    public function delete(int $id): void
    {
        try {
            Location::query()->findOrFail($id)->delete();
            $this->dispatch('notify', message: 'Lokasi dihapus.', type: 'success');
        } catch (QueryException) {
            $this->dispatch('notify', message: 'Lokasi tidak bisa dihapus karena masih memiliki data transaksi. Nonaktifkan saja.', type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.settings.locations', [
            'locations' => Location::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(25),
            'types' => LocationType::cases(),
        ]);
    }
}
