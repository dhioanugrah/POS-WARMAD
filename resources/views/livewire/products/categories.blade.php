<div>
    <x-page-header title="Kategori & Merek" description="Kelola jenis dan merek barang.">
        <x-slot:actions>
            <x-button wire:click="create">
                <x-icon name="plus" class="size-4" /> Tambah {{ $activeTab === 'kategori' ? 'Kategori' : 'Merek' }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 flex gap-1 rounded-lg bg-slate-100 p-1 max-w-xs">
        <button type="button" wire:click="$set('activeTab', 'kategori')" class="flex-1 rounded-md px-4 py-2 text-sm font-medium transition {{ $activeTab === 'kategori' ? 'bg-white shadow text-slate-900' : 'text-slate-600' }}">Kategori</button>
        <button type="button" wire:click="$set('activeTab', 'merek')" class="flex-1 rounded-md px-4 py-2 text-sm font-medium transition {{ $activeTab === 'merek' ? 'bg-white shadow text-slate-900' : 'text-slate-600' }}">Merek</button>
    </div>

    @if($activeTab === 'kategori')
        @if($categories->isEmpty())
            <x-empty-state title="Belum ada kategori" />
        @else
            <x-table>
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Jumlah Barang</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($categories as $category)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $category->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $category->products_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="edit({{ $category->id }})" class="text-slate-500 hover:text-emerald-600"><x-icon name="pencil" class="size-4" /></button>
                                <button wire:click="delete({{ $category->id }})" wire:confirm="Hapus kategori ini?" class="ml-2 text-slate-500 hover:text-rose-600"><x-icon name="trash" class="size-4" /></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
            <div class="mt-4">{{ $categories->links() }}</div>
        @endif
    @else
        @if($brands->isEmpty())
            <x-empty-state title="Belum ada merek" />
        @else
            <x-table>
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Jumlah Barang</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($brands as $brand)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $brand->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $brand->products_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="edit({{ $brand->id }})" class="text-slate-500 hover:text-emerald-600"><x-icon name="pencil" class="size-4" /></button>
                                <button wire:click="delete({{ $brand->id }})" wire:confirm="Hapus merek ini?" class="ml-2 text-slate-500 hover:text-rose-600"><x-icon name="trash" class="size-4" /></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
            <div class="mt-4">{{ $brands->links() }}</div>
        @endif
    @endif

    <x-modal :show="$showModal" maxWidth="md">
        <form wire:submit="save" class="p-6">
            <h2 class="text-lg font-semibold text-slate-900">
                {{ $editingId ? 'Ubah' : 'Tambah' }} {{ $activeTab === 'kategori' ? 'Kategori' : 'Merek' }}
            </h2>
            <div class="mt-4 space-y-4">
                <x-input label="Nama" wire:model="name" />
                @if($activeTab === 'kategori')
                    <x-input label="Deskripsi (opsional)" wire:model="description" />
                @endif
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <x-button type="button" variant="secondary" wire:click="$set('showModal', false)">Batal</x-button>
                <x-button type="submit">Simpan</x-button>
            </div>
        </form>
    </x-modal>
</div>
