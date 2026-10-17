<div x-data x-on:focus-search.window="$refs.searchInput?.focus()">
    <x-page-header title="Saldo Awal Stok" description="Input stok awal beserta harga pokok untuk go-live." />

    <div class="mb-4 max-w-xs">
        <x-select label="Lokasi" wire:model.live="locationId" :options="$locations" />
    </div>

    <div class="mb-4 relative max-w-md">
        <x-input x-ref="searchInput" wire:model.live.debounce.300ms="search" wire:keydown.enter="scan" placeholder="Scan barcode atau cari nama barang..." autofocus />
        <p class="mt-1 text-xs text-slate-400">Scan barcode lalu Enter: baris otomatis bertambah 1 tiap scan. Isi harga pokok cukup sekali di awal.</p>
        @if($results->isNotEmpty())
            <div class="absolute z-10 mt-1 w-full rounded-lg bg-white shadow-lg ring-1 ring-slate-200 divide-y divide-slate-100">
                @foreach($results as $product)
                    <button type="button" wire:click="addProduct({{ $product->id }})" class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-slate-50">
                        <span>{{ $product->name }} <span class="text-xs text-slate-400">{{ $product->code }}</span></span>
                        @if($existingProductIds->contains($product->id))
                            <x-badge variant="info">Sudah ada</x-badge>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @error('rows') <p class="mb-3 text-sm text-rose-600">{{ $message }}</p> @enderror

    @if(empty($rows))
        <x-empty-state title="Belum ada barang ditambahkan" />
    @else
        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Barang</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Satuan</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Qty</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Harga Pokok / Satuan</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($rows as $index => $row)
                    <tr wire:key="row-{{ $index }}">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $row['product_name'] }}</td>
                        <td class="px-4 py-3 w-32">
                            <select wire:change="changeUnit({{ $index }}, $event.target.value)" class="block w-full rounded-lg border-0 py-1.5 px-2 text-sm shadow-sm ring-1 ring-inset ring-slate-300">
                                @foreach($row['units'] as $unit)
                                    <option value="{{ $unit['id'] }}" @selected($unit['id'] === $row['unit_id'])>{{ $unit['unit_name'] }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-3 w-28">
                            <input type="number" step="0.001" wire:model="rows.{{ $index }}.qty" class="block w-full rounded-lg border-0 py-1.5 px-2 text-right text-sm shadow-sm ring-1 ring-inset ring-slate-300">
                        </td>
                        <td class="px-4 py-3 w-36">
                            <input type="number" step="1" wire:model="rows.{{ $index }}.unit_cost" class="block w-full rounded-lg border-0 py-1.5 px-2 text-right text-sm shadow-sm ring-1 ring-inset ring-slate-300">
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="removeRow({{ $index }})" class="text-slate-400 hover:text-rose-600"><x-icon name="trash" class="size-4" /></button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>

        <div class="mt-4 flex justify-end">
            <x-button wire:click="save">Simpan Saldo Awal</x-button>
        </div>
    @endif
</div>
