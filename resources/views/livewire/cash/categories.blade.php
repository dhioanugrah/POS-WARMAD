<div>
    <x-page-header title="Kategori Kas">
        <x-slot:actions>
            <a href="{{ route('cash.index') }}" wire:navigate><x-button variant="secondary">Kembali</x-button></a>
            <x-button wire:click="create"><x-icon name="plus" class="size-4" /> Tambah Kategori</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tipe</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($categories as $category)
                <tr>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $category->name }}</td>
                    <td class="px-4 py-3"><x-badge :variant="$category->type->value === 'in' ? 'success' : 'danger'">{{ $category->type->value === 'in' ? 'Masuk' : 'Keluar' }}</x-badge></td>
                    <td class="px-4 py-3">
                        <button wire:click="toggleActive({{ $category->id }})">
                            <x-badge :variant="$category->is_active ? 'success' : 'neutral'">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                        </button>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button wire:click="edit({{ $category->id }})" class="text-slate-500 hover:text-emerald-600"><x-icon name="pencil" class="size-4" /></button>
                        <button wire:click="delete({{ $category->id }})" wire:confirm="Hapus kategori ini?" class="ml-2 text-slate-500 hover:text-rose-600"><x-icon name="trash" class="size-4" /></button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>

    <x-modal :show="$showModal" maxWidth="sm">
        <form wire:submit="save" class="p-6">
            <h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Ubah' : 'Tambah' }} Kategori Kas</h2>
            <div class="mt-4 space-y-4">
                <x-input label="Nama" wire:model="name" />
                <x-select label="Tipe" wire:model="type" :options="['in' => 'Masuk', 'out' => 'Keluar']" />
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <x-button type="button" variant="secondary" wire:click="$set('showModal', false)">Batal</x-button>
                <x-button type="submit">Simpan</x-button>
            </div>
        </form>
    </x-modal>
</div>
