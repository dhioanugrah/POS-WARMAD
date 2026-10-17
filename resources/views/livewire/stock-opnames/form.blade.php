@php use App\Support\UnitFormatter; @endphp
<div>
    <x-page-header :title="$opnameId ? 'Stok Opname' : 'Sesi Opname Baru'">
        <x-slot:actions>
            <a href="{{ route('stock-opnames.index') }}" wire:navigate><x-button variant="secondary">Kembali</x-button></a>
        </x-slot:actions>
    </x-page-header>

    @if(!$opnameId)
        <div class="max-w-lg rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
            <div class="space-y-4">
                <x-select label="Lokasi" wire:model="locationId" :options="$locations" />
                <x-select label="Cakupan" wire:model="scopeCategoryId" placeholder="Semua Barang" :options="$categories" />
                <x-input label="Catatan" wire:model="notes" />
            </div>
            <div class="mt-6 flex justify-end">
                <x-button wire:click="createSession">Buat Sesi</x-button>
            </div>
        </div>
    @else
        @if($status?->value === 'draft')
            <div class="relative mb-4 max-w-md" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
                <x-input x-on:focus="open = true" wire:model.live.debounce.300ms="scanInput" wire:keydown.enter.prevent="scan" placeholder="Scan barcode / kode barang..." />
                <p class="mt-1 text-xs text-slate-400">Scan barcode lalu Enter: hitungan fisik bertambah 1 satuan tiap scan (barcode dus = isi 1 dus). Atau klik kolom ini untuk memilih dari daftar barcode.</p>
                <div x-show="open" x-cloak class="absolute z-10 mt-1 max-h-96 w-full overflow-y-auto rounded-lg bg-white shadow-lg ring-1 ring-slate-200">
                    <x-barcode-picker :options="$barcodeOptions" />
                </div>
            </div>
        @endif

        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Barang</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Sistem</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Fisik</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Selisih</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($items as $item)
                    <tr wire:key="opitem-{{ $item->id }}">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $item->product->name }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ UnitFormatter::breakdown($item->product, $item->system_qty) }}</td>
                        <td class="px-4 py-3 w-32 text-right">
                            @if($status?->value === 'draft')
                                <input type="number" step="0.001" value="{{ $item->counted_qty }}" wire:change="updateCount({{ $item->id }}, $event.target.value)" class="w-full rounded-lg border-0 py-1 px-2 text-right ring-1 ring-slate-300">
                            @else
                                {{ $item->counted_qty !== null ? UnitFormatter::formatQty((float) $item->counted_qty) : '-' }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right {{ $item->difference < 0 ? 'text-rose-600' : ($item->difference > 0 ? 'text-emerald-600' : 'text-slate-400') }}">
                            {{ $item->counted_qty !== null ? UnitFormatter::formatQty((float) $item->difference) : '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>

        @if($status?->value === 'draft')
            <div class="mt-4 flex justify-end">
                <x-button wire:click="post" wire:confirm="Posting opname ini? Stok akan disesuaikan sesuai hitungan fisik.">Posting</x-button>
            </div>
        @endif
    @endif
</div>
