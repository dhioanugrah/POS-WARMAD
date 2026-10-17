<div>
    <x-page-header title="Penyesuaian Stok" description="Barang keluar lain-lain: rusak, hilang, kadaluarsa, pemakaian sendiri.">
        <x-slot:actions>
            <a href="{{ route('stock-adjustments.create') }}" wire:navigate><x-button><x-icon name="plus" class="size-4" /> Penyesuaian Baru</x-button></a>
        </x-slot:actions>
    </x-page-header>

    @if($adjustments->isEmpty())
        <x-empty-state title="Belum ada penyesuaian stok" />
    @else
        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">No.</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Lokasi</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Alasan</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Item</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($adjustments as $adj)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $adj->number }}</td>
                        <td class="px-4 py-3">{{ $adj->location->name }}</td>
                        <td class="px-4 py-3"><x-badge variant="warning">{{ ucfirst($adj->reason->value) }}</x-badge></td>
                        <td class="px-4 py-3 text-slate-600">{{ $adj->adjusted_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">{{ $adj->items->count() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
        <div class="mt-4">{{ $adjustments->links() }}</div>
    @endif
</div>
