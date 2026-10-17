@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Pembelian" description="Daftar pembelian dari supplier.">
        <x-slot:actions>
            @can('purchases.manage')
                <a href="{{ route('purchases.create') }}" wire:navigate><x-button><x-icon name="plus" class="size-4" /> Pembelian Baru</x-button></a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <x-select wire:model.live="supplierFilter" placeholder="Semua Supplier" :options="$suppliers" />
        <x-select wire:model.live="statusFilter" placeholder="Semua Status" :options="['draft' => 'Draft', 'posted' => 'Posted', 'void' => 'Void']" />
    </div>

    @if($purchases->isEmpty())
        <x-empty-state title="Belum ada pembelian" />
    @else
        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">No.</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Supplier</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($purchases as $purchase)
                    <tr class="cursor-pointer hover:bg-slate-50" onclick="window.location='{{ route('purchases.show', $purchase) }}'">
                        <td class="px-4 py-3 font-mono text-xs">{{ $purchase->number }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $purchase->purchased_at->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $purchase->supplier->name }}</td>
                        <td class="px-4 py-3 text-right">{{ Money::format($purchase->total) }}</td>
                        <td class="px-4 py-3">
                            <x-badge :variant="match($purchase->status->value) { 'posted' => 'success', 'void' => 'danger', default => 'neutral' }">{{ ucfirst($purchase->status->value) }}</x-badge>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
        <div class="mt-4">{{ $purchases->links() }}</div>
    @endif
</div>
