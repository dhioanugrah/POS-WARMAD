<div>
    <x-page-header title="Lokasi" description="Kelola toko dan gudang.">
        <x-slot:actions>
            <x-button wire:click="create">
                <x-icon name="plus" class="size-4" /> Tambah Lokasi
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Cari kode / nama lokasi..." class="max-w-sm" />
    </div>

    @if($locations->isEmpty())
        <x-empty-state title="Belum ada lokasi" description="Tambahkan lokasi toko atau gudang pertama Anda." />
    @else
        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kode</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tipe</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($locations as $location)
                    <tr>
                        <td class="px-4 py-3 font-mono text-slate-600">{{ $location->code }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $location->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $location->type === \App\Enums\LocationType::Store ? 'Toko' : 'Gudang' }}</td>
                        <td class="px-4 py-3">
                            <x-badge :variant="$location->is_active ? 'success' : 'neutral'">{{ $location->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="edit({{ $location->id }})" class="text-slate-500 hover:text-emerald-600"><x-icon name="pencil" class="size-4" /></button>
                            <button wire:click="delete({{ $location->id }})" wire:confirm="Hapus lokasi ini?" class="ml-2 text-slate-500 hover:text-rose-600"><x-icon name="trash" class="size-4" /></button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
        <div class="mt-4">{{ $locations->links() }}</div>
    @endif

    <x-modal :show="$showModal" maxWidth="md">
        <form wire:submit="save" class="p-6">
            <h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Ubah Lokasi' : 'Tambah Lokasi' }}</h2>
            <div class="mt-4 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <x-input label="Kode" wire:model="code" maxlength="10" />
                    <x-select label="Tipe" wire:model="type" :options="['store' => 'Toko', 'warehouse' => 'Gudang']" />
                </div>
                <x-input label="Nama" wire:model="name" />
                <x-input label="Alamat" wire:model="address" />
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
                    Aktif
                </label>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <x-button type="button" variant="secondary" wire:click="$set('showModal', false)">Batal</x-button>
                <x-button type="submit">Simpan</x-button>
            </div>
        </form>
    </x-modal>
</div>
