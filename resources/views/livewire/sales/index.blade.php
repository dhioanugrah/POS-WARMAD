@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Riwayat Penjualan" description="Semua transaksi kasir." />

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach(['today' => 'Hari ini', 'yesterday' => 'Kemarin', '7days' => '7 hari', 'this_month' => 'Bulan ini', 'last_month' => 'Bulan lalu'] as $key => $label)
            <button type="button" wire:click="setPreset('{{ $key }}')" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 hover:bg-slate-200">{{ $label }}</button>
        @endforeach
    </div>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <x-input type="date" wire:model.live="dateFrom" />
        <x-input type="date" wire:model.live="dateTo" />
        <x-input wire:model.live.debounce.300ms="search" placeholder="Cari no. nota..." />
        <x-select wire:model.live="cashierFilter" placeholder="Semua Kasir" :options="$cashiers" />
        <x-select wire:model.live="methodFilter" placeholder="Semua Metode" :options="['cash' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'debit_card' => 'Kartu']" />
        <x-select wire:model.live="statusFilter" placeholder="Semua Status" :options="['completed' => 'Selesai', 'void' => 'Void']" />
    </div>

    @if($sales->isEmpty())
        <x-empty-state title="Tidak ada transaksi" />
    @else
        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">No. Nota</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kasir</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pelanggan</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($sales as $sale)
                    <tr class="cursor-pointer hover:bg-slate-50" onclick="window.location='{{ route('sales.show', $sale) }}'">
                        <td class="px-4 py-3 font-mono text-xs">{{ $sale->number }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $sale->sold_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $sale->cashier->name }}</td>
                        <td class="px-4 py-3">{{ $sale->customer->name ?? 'Umum' }}</td>
                        <td class="px-4 py-3 text-right {{ $sale->status->value === 'void' ? 'line-through text-slate-400' : '' }}">{{ Money::format($sale->total) }}</td>
                        <td class="px-4 py-3">
                            <x-badge :variant="$sale->status->value === 'void' ? 'danger' : 'success'">{{ $sale->status->value === 'void' ? 'VOID' : 'Selesai' }}</x-badge>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
        <div class="mt-4">{{ $sales->links() }}</div>
    @endif
</div>
