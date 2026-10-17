@php use App\Support\UnitFormatter; use App\Support\Money; @endphp
<div>
    <x-page-header title="Posisi Stok" description="Stok barang per lokasi saat ini." />

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Cari nama / kode..." />
        <x-select wire:model.live="categoryFilter" placeholder="Semua Kategori" :options="$categories" />
        <x-select wire:model.live="locationFilter" placeholder="Semua Lokasi" :options="$locations" />
    </div>

    @if($products->isEmpty())
        <x-empty-state title="Tidak ada barang" />
    @else
        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kode</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kategori</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Stok</th>
                    @if($canViewCost)
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">HPP</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Nilai</th>
                    @endif
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($products as $product)
                    @php $qty = (float) ($product->stocks_sum_qty ?? 0); @endphp
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $product->code }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $product->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $product->category?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right {{ $qty <= $product->min_stock ? 'text-amber-600 font-medium' : '' }}">{{ UnitFormatter::breakdown($product, $qty) }}</td>
                        @if($canViewCost)
                            <td class="px-4 py-3 text-right text-slate-600">{{ Money::format($product->avg_cost) }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ Money::format($qty * (float) $product->avg_cost) }}</td>
                        @endif
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('stock.card', $product) }}" wire:navigate class="text-emerald-600 hover:underline text-xs font-medium">Kartu Stok</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
        <div class="mt-4">{{ $products->links() }}</div>

        @if($canViewCost)
            <div class="mt-4 flex justify-end">
                <div class="rounded-xl bg-white px-6 py-4 shadow-sm ring-1 ring-slate-900/5">
                    <span class="text-sm text-slate-500">Total Nilai Persediaan: </span>
                    <span class="text-lg font-semibold text-slate-900">{{ Money::format($totalValue) }}</span>
                </div>
            </div>
        @endif
    @endif
</div>
