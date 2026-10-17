@php
    use App\Support\UnitFormatter;

    $typeLabels = [
        'opening' => 'Saldo Awal', 'purchase' => 'Pembelian', 'purchase_return' => 'Retur Beli', 'purchase_void' => 'Void Pembelian',
        'sale' => 'Penjualan', 'sale_return' => 'Retur Jual', 'sale_void' => 'Void Penjualan',
        'transfer_in' => 'Transfer Masuk', 'transfer_out' => 'Transfer Keluar', 'adjustment' => 'Penyesuaian', 'opname' => 'Opname',
    ];

    $referenceUrl = function ($movement) {
        $ref = $movement->reference;
        if (! $ref) return null;
        return match ($movement->reference_type) {
            \App\Models\Sale::class => Route::has('sales.show') ? route('sales.show', $ref->id) : null,
            \App\Models\Purchase::class => Route::has('purchases.show') ? route('purchases.show', $ref->id) : null,
            default => null,
        };
    };
@endphp
<div>
    <x-page-header title="Kartu Stok: {{ $product->name }}" description="Kode {{ $product->code }}">
        <x-slot:actions>
            <a href="{{ route('stock.index') }}" wire:navigate><x-button variant="secondary">Kembali</x-button></a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <x-select wire:model.live="locationFilter" placeholder="Semua Lokasi" :options="$locations" />
        <x-input type="date" wire:model.live="dateFrom" label="" />
        <x-input type="date" wire:model.live="dateTo" label="" />
    </div>

    <x-table>
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Dokumen</th>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Masuk</th>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Keluar</th>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Saldo</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <tr class="bg-slate-50 font-medium">
                <td class="px-4 py-2" colspan="5">Saldo Awal Periode</td>
                <td class="px-4 py-2 text-right">{{ UnitFormatter::breakdown($product, $openingBalance) }}</td>
            </tr>
            @foreach($movements as $movement)
                <tr>
                    <td class="px-4 py-3 text-slate-500">{{ $movement->occurred_at->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3">{{ $typeLabels[$movement->type->value] ?? $movement->type->value }}</td>
                    <td class="px-4 py-3">
                        @if($referenceUrl($movement))
                            <a href="{{ $referenceUrl($movement) }}" wire:navigate class="text-emerald-600 hover:underline">{{ $movement->reference->number ?? '-' }}</a>
                        @else
                            <span class="text-slate-400">{{ $movement->note ?? '-' }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right text-emerald-600">{{ $movement->qty_change > 0 ? UnitFormatter::formatQty((float) $movement->qty_change) : '' }}</td>
                    <td class="px-4 py-3 text-right text-rose-600">{{ $movement->qty_change < 0 ? UnitFormatter::formatQty(abs((float) $movement->qty_change)) : '' }}</td>
                    <td class="px-4 py-3 text-right font-medium">{{ UnitFormatter::breakdown($product, $movement->qty_after) }}</td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
    <div class="mt-4">{{ $movements->links() }}</div>
</div>
