<?php

namespace App\Livewire\Products;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Categories extends Component
{
    public string $activeTab = 'kategori';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'description']);
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $model = $this->activeTab === 'kategori'
            ? Category::query()->findOrFail($id)
            : Brand::query()->findOrFail($id);

        $this->editingId = $model->id;
        $this->name = $model->name;
        $this->description = $model->description ?? '';
        $this->showModal = true;
    }

    public function rules(): array
    {
        $table = $this->activeTab === 'kategori' ? 'categories' : 'brands';

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique($table, 'name')->ignore($this->editingId)],
            'description' => 'nullable|string|max:255',
        ];
    }

    public function save(): void
    {
        $this->validate();

        if ($this->activeTab === 'kategori') {
            Category::query()->updateOrCreate(
                ['id' => $this->editingId],
                ['name' => $this->name, 'description' => $this->description]
            );
        } else {
            Brand::query()->updateOrCreate(['id' => $this->editingId], ['name' => $this->name]);
        }

        $this->showModal = false;
        $this->dispatch('notify', message: 'Tersimpan.', type: 'success');
    }

    public function delete(int $id): void
    {
        try {
            if ($this->activeTab === 'kategori') {
                $category = Category::query()->withCount('products')->findOrFail($id);
                if ($category->products_count > 0) {
                    $this->dispatch('notify', message: "Kategori masih dipakai {$category->products_count} barang.", type: 'error');

                    return;
                }
                $category->delete();
            } else {
                $brand = Brand::query()->withCount('products')->findOrFail($id);
                if ($brand->products_count > 0) {
                    $this->dispatch('notify', message: "Merek masih dipakai {$brand->products_count} barang.", type: 'error');

                    return;
                }
                $brand->delete();
            }

            $this->dispatch('notify', message: 'Dihapus.', type: 'success');
        } catch (QueryException) {
            $this->dispatch('notify', message: 'Tidak bisa dihapus karena masih dipakai.', type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.products.categories', [
            'categories' => $this->activeTab === 'kategori'
                ? Category::withCount('products')->orderBy('name')->paginate(25, pageName: 'kategori-page')
                : null,
            'brands' => $this->activeTab === 'merek'
                ? Brand::withCount('products')->orderBy('name')->paginate(25, pageName: 'merek-page')
                : null,
        ]);
    }
}
