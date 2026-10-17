<?php

namespace App\Livewire\Cash;

use App\Models\CashCategory;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Categories extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $type = 'out';

    public function create(): void
    {
        $this->reset(['editingId', 'name']);
        $this->type = 'out';
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $category = CashCategory::query()->findOrFail($id);
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->type = $category->type->value;
        $this->showModal = true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('cash_categories', 'name')->ignore($this->editingId)],
            'type' => 'required|in:in,out',
        ];
    }

    public function save(): void
    {
        $this->validate();

        CashCategory::query()->updateOrCreate(
            ['id' => $this->editingId],
            ['name' => $this->name, 'type' => $this->type, 'is_active' => true]
        );

        $this->showModal = false;
        $this->dispatch('notify', message: 'Kategori kas tersimpan.', type: 'success');
    }

    public function toggleActive(int $id): void
    {
        $category = CashCategory::query()->findOrFail($id);
        $category->update(['is_active' => ! $category->is_active]);
    }

    public function delete(int $id): void
    {
        $category = CashCategory::query()->findOrFail($id);

        if ($category->transactions()->exists()) {
            $this->dispatch('notify', message: 'Kategori sudah dipakai, tidak bisa dihapus. Nonaktifkan saja.', type: 'error');

            return;
        }

        $category->delete();
        $this->dispatch('notify', message: 'Kategori dihapus.', type: 'success');
    }

    public function render()
    {
        return view('livewire.cash.categories', [
            'categories' => CashCategory::query()->orderBy('type')->orderBy('name')->get(),
        ]);
    }
}
