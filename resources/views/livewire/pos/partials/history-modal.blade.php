@php use App\Support\Money; @endphp
<x-modal :show="$showHistoryModal" close="showHistoryModal" maxWidth="xl">
    <div class="p-6">
        <h2 class="text-lg font-semibold text-slate-900">Riwayat Transaksi Shift Ini</h2>
        <div class="mt-4 max-h-96 overflow-y-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="py-2">No. Nota</th>
                        <th class="py-2">Waktu</th>
                        <th class="py-2 text-right">Total</th>
                        <th class="py-2">Status</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($shiftSales as $sale)
                        <tr>
                            <td class="py-2 font-mono text-xs">{{ $sale->number }}</td>
                            <td class="py-2 text-slate-500">{{ $sale->sold_at->format('H:i') }}</td>
                            <td class="py-2 text-right {{ $sale->status->value === 'void' ? 'line-through text-slate-400' : '' }}">{{ Money::format($sale->total) }}</td>
                            <td class="py-2">
                                <x-badge :variant="$sale->status->value === 'void' ? 'danger' : 'success'">{{ $sale->status->value === 'void' ? 'VOID' : 'Selesai' }}</x-badge>
                            </td>
                            <td class="py-2 text-right whitespace-nowrap">
                                <button type="button" wire:click="$dispatch('print-receipt', { saleId: {{ $sale->id }}, copy: true })" class="text-slate-500 hover:text-emerald-600"><x-icon name="printer" class="size-4" /></button>
                                @can('sales.void')
                                    @if($sale->status->value !== 'void')
                                        <button
                                            type="button"
                                            x-data
                                            x-on:click="let r = prompt('Alasan void:'); if (r) { $wire.voidSale({{ $sale->id }}, r) }"
                                            class="ml-2 text-slate-500 hover:text-rose-600"
                                        ><x-icon name="x-circle" class="size-4" /></button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-slate-400">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex justify-end">
            <x-button type="button" variant="secondary" wire:click="$set('showHistoryModal', false)">Tutup</x-button>
        </div>
    </div>
</x-modal>
