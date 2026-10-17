@php use App\Support\UnitFormatter; use App\Support\Money; @endphp
<div>
    <x-page-header title="Barang" description="Daftar barang, satuan, dan harga jual.">
        <x-slot:actions>
            @can('products.manage')
                <a href="{{ route('products.create') }}" wire:navigate>
                    <x-button><x-icon name="plus" class="size-4" /> Tambah Barang</x-button>
                </a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <x-input wire:model.live.debounce.300ms="search" placeholder="Cari nama / kode / barcode..." />
        </div>
        <x-select wire:model.live="categoryFilter" placeholder="Semua Kategori" :options="$categories" />
        <x-select wire:model.live="statusFilter" placeholder="Semua Status" :options="['active' => 'Aktif', 'inactive' => 'Nonaktif']" />
    </div>
    <label class="mb-4 flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" wire:model.live="lowStockOnly" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
        Hanya tampilkan stok menipis
    </label>

    @if($products->isEmpty())
        <x-empty-state title="Belum ada barang" description="Tambahkan barang pertama Anda." />
    @else
        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kode</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kategori</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Stok</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Harga Jual</th>
                    @can('products.view-cost')
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">HPP</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Margin</th>
                    @endcan
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($products as $product)
                    @php
                        $baseUnit = $product->units->firstWhere('is_base', true) ?? $product->units->first();
                        $isLow = $product->type->value === 'stock' && (float) $product->total_stock <= (float) $product->min_stock;
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $product->code }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $product->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $product->category?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($product->type->value === 'service')
                                <span class="text-slate-400">Jasa</span>
                            @else
                                <span class="{{ $isLow ? 'text-amber-600 font-medium' : 'text-slate-700' }}">
                                    {{ UnitFormatter::breakdown($product, $product->total_stock ?? 0) }}
                                </span>
                                @if($isLow)
                                    <x-badge variant="warning" class="ml-1">Menipis</x-badge>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">{{ Money::format($baseUnit?->price_sell ?? 0) }}</td>
                        @can('products.view-cost')
                            <td class="px-4 py-3 text-right text-slate-600">{{ Money::format($product->avg_cost) }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">
                                @php
                                    $sell = (float) ($baseUnit?->price_sell ?? 0);
                                    $margin = $sell > 0 ? round((($sell - (float) $product->avg_cost) / $sell) * 100, 1) : null;
                                @endphp
                                {{ $margin === null ? '-' : $margin.'%' }}
                            </td>
                        @endcan
                        <td class="px-4 py-3">
                            <x-badge :variant="$product->is_active ? 'success' : 'neutral'">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @can('products.manage')
                                <a href="{{ route('products.edit', $product) }}" wire:navigate class="text-slate-500 hover:text-emerald-600"><x-icon name="pencil" class="size-4 inline" /></a>
                                <button wire:click="toggleActive({{ $product->id }})" class="ml-2 text-slate-500 hover:text-amber-600" title="Aktif/Nonaktifkan"><x-icon name="eye" class="size-4 inline" /></button>
                                <button wire:click="delete({{ $product->id }})" wire:confirm="Hapus permanen barang ini?" class="ml-2 text-slate-500 hover:text-rose-600"><x-icon name="trash" class="size-4 inline" /></button>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
        <div class="mt-4">{{ $products->links() }}</div>
    @endif
</div>
