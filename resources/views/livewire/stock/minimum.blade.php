@php use App\Support\UnitFormatter; @endphp
<div>
    <x-page-header title="Stok Minimum" description="Barang dengan stok di bawah atau sama dengan batas minimum." />

    @if($products->isEmpty())
        <x-empty-state title="Semua stok aman" description="Tidak ada barang di bawah stok minimum." />
    @else
        <div class="mb-4 flex items-center gap-2">
            <x-select wire:model="supplierId" placeholder="- Pilih Supplier untuk draft pembelian -" :options="$suppliers" class="max-w-xs" />
            <x-button wire:click="createDraftPurchase">Buat Draft Pembelian</x-button>
        </div>
        @error('supplierId') <p class="mb-3 text-sm text-rose-600">{{ $message }}</p> @enderror

        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Barang</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Stok Saat Ini</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Min. Stok</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Saran Beli</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($products as $product)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $product->name }}</td>
                        <td class="px-4 py-3 text-right text-amber-600 font-medium">{{ UnitFormatter::breakdown($product, $product->current_qty) }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ UnitFormatter::formatQty((float) $product->min_stock) }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ UnitFormatter::formatQty($product->suggested_qty) }} {{ $product->base_unit }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    @endif
</div>
