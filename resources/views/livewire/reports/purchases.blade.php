@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Laporan Pembelian">
        <x-slot:actions>
            <a href="{{ route('reports.index') }}" wire:navigate><x-button variant="secondary">Kembali</x-button></a>
        </x-slot:actions>
    </x-page-header>

    @include('livewire.reports.partials.date-filter')
    <div class="mb-4 max-w-xs">
        <x-select wire:model.live="supplierFilter" placeholder="Semua Supplier" :options="$suppliers" />
    </div>

    <div class="mb-4 max-w-xs">
        <x-stat-card label="Total Pembelian" :value="Money::format($total)" />
    </div>

    @if($purchases->isEmpty())
        <x-empty-state title="Tidak ada pembelian pada periode ini" />
    @else
        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">No.</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Supplier</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($purchases as $purchase)
                    <tr class="cursor-pointer hover:bg-slate-50" onclick="window.location='{{ route('purchases.show', $purchase) }}'">
                        <td class="px-4 py-3 font-mono text-xs">{{ $purchase->number }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $purchase->purchased_at->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $purchase->supplier->name }}</td>
                        <td class="px-4 py-3 text-right">{{ Money::format($purchase->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    @endif
</div>
