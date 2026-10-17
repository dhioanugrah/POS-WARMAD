<div>
    <button type="button" wire:click="open" class="flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">
        <x-icon name="users" class="size-4 text-slate-400" />
        {{ $selectedCustomer->name ?? 'Umum' }}
        <span class="text-xs text-slate-400">(F4)</span>
    </button>

    <x-modal :show="$show" close="show" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-slate-900">Pilih Pelanggan</h2>
            <div class="mt-4">
                <input
                    type="text"
                    wire:model.live.debounce.200ms="search"
                    placeholder="Cari nama atau telepon..."
                    autofocus
                    class="block w-full rounded-lg border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 sm:text-sm"
                >
            </div>

            <div class="mt-3 max-h-64 overflow-y-auto divide-y divide-slate-100 rounded-lg ring-1 ring-slate-100">
                <button type="button" wire:click="select(null)" class="flex w-full items-center justify-between px-3 py-2.5 text-left text-sm hover:bg-slate-50">
                    <span class="font-medium text-slate-900">Umum</span>
                    <span class="text-xs text-slate-400">Tanpa data pelanggan</span>
                </button>
                @foreach($results as $customer)
                    <button type="button" wire:click="select({{ $customer->id }})" class="flex w-full items-center justify-between px-3 py-2.5 text-left text-sm hover:bg-slate-50">
                        <span>
                            <span class="font-medium text-slate-900">{{ $customer->name }}</span>
                            <span class="ml-2 text-xs text-slate-400">{{ $customer->code }}</span>
                        </span>
                        <span class="text-xs text-slate-500">{{ $customer->phone }}</span>
                    </button>
                @endforeach

                @if($search && $results->isEmpty())
                    <div class="px-3 py-4 text-center text-sm text-slate-500">Tidak ditemukan.</div>
                @endif
            </div>

            <div class="mt-4 flex justify-between">
                <x-button type="button" variant="ghost" size="sm" wire:click="openQuickAdd">
                    <x-icon name="plus" class="size-4" /> Tambah Cepat
                </x-button>
                <x-button type="button" variant="secondary" wire:click="$set('show', false)">Tutup</x-button>
            </div>
        </div>
    </x-modal>

    <x-modal :show="$showQuickAdd" close="showQuickAdd" maxWidth="sm">
        <form wire:submit="quickAdd" class="p-6">
            <h2 class="text-lg font-semibold text-slate-900">Tambah Pelanggan Cepat</h2>
            <div class="mt-4 space-y-4">
                <x-input label="Nama" wire:model="quickAddName" autofocus />
                <x-input label="Telepon" wire:model="quickAddPhone" />
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <x-button type="button" variant="secondary" wire:click="$set('showQuickAdd', false)">Batal</x-button>
                <x-button type="submit">Simpan & Pilih</x-button>
            </div>
        </form>
    </x-modal>
</div>
