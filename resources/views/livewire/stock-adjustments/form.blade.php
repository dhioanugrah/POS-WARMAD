<div>
    <x-page-header title="Penyesuaian Stok Baru">
        <x-slot:actions>
            <a href="{{ route('stock-adjustments.index') }}" wire:navigate><x-button variant="secondary">Kembali</x-button></a>
        </x-slot:actions>
    </x-page-header>

    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
        <div class="grid gap-4 sm:grid-cols-3">
            <x-select label="Lokasi" wire:model="locationId" :options="$locations" />
            <x-select label="Alasan" wire:model="reason" :options="['damaged' => 'Rusak', 'lost' => 'Hilang', 'expired' => 'Kadaluarsa', 'own_use' => 'Pemakaian Sendiri', 'other' => 'Lainnya']" />
            <x-input label="Catatan" wire:model="notes" />
        </div>
    </div>

    <div class="relative mt-4 max-w-md">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Cari barang untuk ditambahkan..." />
        @if($results->isNotEmpty())
            <div class="absolute z-10 mt-1 w-full rounded-lg bg-white shadow-lg ring-1 ring-slate-200 divide-y divide-slate-100">
                @foreach($results as $product)
                    <button type="button" wire:click="addProduct({{ $product->id }})" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50">{{ $product->name }} <span class="text-xs text-slate-400">{{ $product->code }}</span></button>
                @endforeach
            </div>
        @endif
    </div>

    @error('items') <p class="mt-3 text-sm text-rose-600">{{ $message }}</p> @enderror

    <x-table class="mt-4">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Barang</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Satuan</th>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Qty</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($items as $index => $item)
                <tr wire:key="adjitem-{{ $index }}">
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $item['product_name'] }}</td>
                    <td class="px-4 py-3">{{ $item['unit_name'] }}</td>
                    <td class="px-4 py-3 w-28"><input type="number" step="0.001" wire:model="items.{{ $index }}.qty" class="w-full rounded-lg border-0 py-1 px-2 text-right ring-1 ring-slate-300"></td>
                    <td class="px-4 py-3"><button type="button" wire:click="removeItem({{ $index }})" class="text-slate-400 hover:text-rose-600"><x-icon name="trash" class="size-4" /></button></td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-6 text-center text-slate-400">Belum ada barang.</td></tr>
            @endforelse
        </tbody>
    </x-table>

    <div class="mt-4 flex justify-end">
        <x-button wire:click="save">Simpan</x-button>
    </div>
</div>
