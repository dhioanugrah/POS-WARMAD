@php use App\Support\Money; @endphp
<div>
    <x-page-header :title="$purchaseId ? 'Pembelian '.($status?->value === 'draft' ? '(Draft)' : ucfirst($status?->value)) : 'Pembelian Baru'">
        <x-slot:actions>
            <a href="{{ route('purchases.index') }}" wire:navigate><x-button variant="secondary">Kembali</x-button></a>
        </x-slot:actions>
    </x-page-header>

    @if(!empty($marginDrops))
        <div class="mb-4 rounded-xl bg-amber-50 p-4 ring-1 ring-amber-200">
            <p class="mb-2 text-sm font-semibold text-amber-800">Margin beberapa barang turun setelah pembelian ini:</p>
            <div class="space-y-2">
                @foreach($marginDrops as $drop)
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-white p-2" x-data="{ newPrice: {{ $drop['current_price'] }} }">
                        <span class="text-sm">{{ $drop['product_name'] }} ({{ $drop['unit_name'] }}) — HPP baru {{ Money::format($drop['new_cost']) }}, harga jual {{ Money::format($drop['current_price']) }}</span>
                        <div class="flex items-center gap-2">
                            <input type="number" x-model="newPrice" class="w-28 rounded-lg border-0 py-1 px-2 text-sm ring-1 ring-slate-300">
                            <x-button type="button" size="sm" x-on:click="$wire.applyPriceSuggestion({{ $drop['product_unit_id'] }}, newPrice)">Terapkan</x-button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Supplier</label>
                <div class="flex gap-2">
                    <div class="flex-1">
                        <x-select wire:model="supplierId" placeholder="- Pilih Supplier -" :options="$suppliers" :disabled="$status?->value !== 'draft'" />
                    </div>
                    @if($status?->value === 'draft')
                        @can('suppliers.manage')
                            <button type="button" wire:click="openQuickAddSupplier" title="Tambah supplier baru" class="flex h-9 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 px-3 text-slate-600 hover:bg-slate-200">
                                <x-icon name="plus" class="size-4" />
                            </button>
                        @endcan
                    @endif
                </div>
            </div>
            <x-select label="Lokasi" wire:model="locationId" :options="$locations" :disabled="$status?->value !== 'draft'" />
            <x-input label="No. Faktur Supplier" wire:model="supplierInvoiceNo" :disabled="$status?->value !== 'draft'" />
            <x-input label="Tanggal" type="date" wire:model="purchasedAt" :disabled="$status?->value !== 'draft'" />
        </div>
    </div>

    @if($status?->value === 'draft')
        <div class="relative mt-4 max-w-md" x-data="{ open: false }" x-on:focus-search.window="$refs.searchInput?.focus()" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
            <x-input x-ref="searchInput" x-on:focus="open = true" wire:model.live.debounce.300ms="search" wire:keydown.enter.prevent="scan" placeholder="Scan barcode atau cari nama barang..." autofocus />
            <p class="mt-1 text-xs text-slate-400">Scan barcode lalu Enter: qty bertambah 1 tiap scan. Scan barcode kardus untuk menambah per kardus. Klik kolom ini untuk memilih dari daftar barcode.</p>
            @if($barcodeOptions->isNotEmpty())
                <div x-show="open && ! $wire.search" x-cloak class="absolute z-10 mt-1 max-h-80 w-full overflow-y-auto rounded-lg bg-white shadow-lg ring-1 ring-slate-200 divide-y divide-slate-100">
                    <p class="px-3 py-2 text-xs font-medium text-slate-500">Barcode yang belum ada di pembelian ini</p>
                    @foreach($barcodeOptions as $option)
                        <button type="button" wire:key="bopt-{{ $option['unit_id'] }}" wire:click="addProductUnit({{ $option['unit_id'] }})" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-slate-50">
                            <span class="min-w-0">
                                <span class="block truncate">{{ $option['product_name'] }} <span class="text-xs text-slate-500">({{ $option['unit_name'] }})</span></span>
                                <span class="block truncate font-mono text-xs text-slate-400">{{ implode(', ', $option['barcodes']) }}</span>
                            </span>
                            <x-badge :variant="$option['stock'] > 0 ? 'neutral' : 'warning'">Stok {{ \App\Support\UnitFormatter::formatQty($option['stock']) }} {{ $option['base_unit'] }}</x-badge>
                        </button>
                    @endforeach
                </div>
            @endif
            @if($results->isNotEmpty())
                <div class="absolute z-10 mt-1 w-full rounded-lg bg-white shadow-lg ring-1 ring-slate-200 divide-y divide-slate-100">
                    @foreach($results as $product)
                        <button type="button" wire:click="addProduct({{ $product->id }})" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50">{{ $product->name }} <span class="text-xs text-slate-400">{{ $product->code }}</span></button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @error('items') <p class="mt-3 text-sm text-rose-600">{{ $message }}</p> @enderror

    <div class="mt-4 overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Barang</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Satuan</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Qty</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Harga</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Diskon</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Subtotal</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $index => $item)
                    <tr wire:key="pitem-{{ $index }}">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $item['product_name'] }}</td>
                        <td class="px-4 py-3 w-28">
                            @if($status?->value === 'draft' && count($item['available_units'] ?? []) > 1)
                                <select wire:change="changeUnit({{ $index }}, $event.target.value)" class="rounded-lg border-0 py-1 px-2 text-xs ring-1 ring-slate-300">
                                    @foreach($item['available_units'] as $u)
                                        <option value="{{ $u['id'] }}" @selected($u['id'] === $item['product_unit_id'])>{{ $u['unit_name'] }}</option>
                                    @endforeach
                                </select>
                            @else
                                {{ $item['unit_name'] }}
                            @endif
                        </td>
                        <td class="px-4 py-3 w-24">
                            <input type="number" step="0.001" wire:model="items.{{ $index }}.qty" @disabled($status?->value !== 'draft') class="w-full rounded-lg border-0 py-1 px-2 text-right ring-1 ring-slate-300">
                        </td>
                        <td class="px-4 py-3 w-32">
                            <input type="number" step="1" wire:model="items.{{ $index }}.price" @disabled($status?->value !== 'draft') class="w-full rounded-lg border-0 py-1 px-2 text-right ring-1 ring-slate-300">
                        </td>
                        <td class="px-4 py-3 w-28">
                            <input type="number" step="1" wire:model="items.{{ $index }}.discount_amount" @disabled($status?->value !== 'draft') class="w-full rounded-lg border-0 py-1 px-2 text-right ring-1 ring-slate-300">
                        </td>
                        <td class="px-4 py-3 text-right">{{ Money::format(((float) $item['qty'] * (float) $item['price']) - (float) ($item['discount_amount'] ?: 0)) }}</td>
                        <td class="px-4 py-3">
                            @if($status?->value === 'draft')
                                <button type="button" wire:click="removeItem({{ $index }})" class="text-slate-400 hover:text-rose-600"><x-icon name="trash" class="size-4" /></button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-slate-400">Belum ada barang.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-end">
        <div class="w-full max-w-xs space-y-2 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-900/5">
            <div class="flex justify-between text-sm"><span class="text-slate-500">Subtotal</span><span>{{ Money::format($this->subtotal) }}</span></div>
            <div class="flex items-center justify-between gap-2 text-sm">
                <span class="text-slate-500">Diskon Faktur</span>
                <input type="number" wire:model="discountAmount" @disabled($status?->value !== 'draft') class="w-28 rounded-lg border-0 py-1 px-2 text-right ring-1 ring-slate-300">
            </div>
            <div class="flex items-center justify-between gap-2 text-sm">
                <span class="text-slate-500">Ongkos Lain</span>
                <input type="number" wire:model="otherCost" @disabled($status?->value !== 'draft') class="w-28 rounded-lg border-0 py-1 px-2 text-right ring-1 ring-slate-300">
            </div>
            <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-semibold"><span>Total</span><span>{{ Money::format($this->total) }}</span></div>
        </div>
    </div>

    <div class="mt-4 flex justify-end gap-2">
        @if($status?->value === 'draft')
            <x-button wire:click="saveDraft">Simpan Draft</x-button>
            @if($purchaseId)
                <x-button variant="secondary" wire:click="post" wire:confirm="Posting pembelian ini? Stok akan bertambah.">Posting</x-button>
            @endif
        @elseif($status?->value === 'posted')
            @can('purchases.void')
                <div x-data="{ reason: '' }" class="flex items-center gap-2">
                    <input x-model="reason" type="text" placeholder="Alasan void" class="rounded-lg border-0 py-2 px-3 text-sm ring-1 ring-slate-300">
                    <x-button variant="danger" x-on:click="if(reason) $wire.void(reason)">Void</x-button>
                </div>
            @endcan
        @endif
    </div>

    <x-modal :show="$showQuickAddSupplier" close="showQuickAddSupplier" maxWidth="sm">
        <form wire:submit="quickAddSupplier" class="p-6">
            <h2 class="text-lg font-semibold text-slate-900">Tambah Supplier Baru</h2>
            <div class="mt-4 space-y-4">
                <x-input label="Nama Supplier" wire:model="quickAddSupplierName" autofocus />
                <x-input label="Telepon (opsional)" wire:model="quickAddSupplierPhone" />
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <x-button type="button" variant="secondary" wire:click="$set('showQuickAddSupplier', false)">Batal</x-button>
                <x-button type="submit">Simpan & Pilih</x-button>
            </div>
        </form>
    </x-modal>
</div>
