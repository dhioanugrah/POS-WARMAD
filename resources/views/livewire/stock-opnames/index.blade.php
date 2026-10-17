<div>
    <x-page-header title="Stok Opname" description="Sesi hitung fisik stok.">
        <x-slot:actions>
            @can('stock.opname')
                <a href="{{ route('stock-opnames.create') }}" wire:navigate><x-button><x-icon name="plus" class="size-4" /> Sesi Baru</x-button></a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if($opnames->isEmpty())
        <x-empty-state title="Belum ada sesi opname" />
    @else
        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">No.</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Lokasi</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($opnames as $opname)
                    <tr class="cursor-pointer hover:bg-slate-50" onclick="window.location='{{ route('stock-opnames.show', $opname) }}'">
                        <td class="px-4 py-3 font-mono text-xs">{{ $opname->number }}</td>
                        <td class="px-4 py-3">{{ $opname->location->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $opname->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <x-badge :variant="match($opname->status->value) { 'posted' => 'success', 'cancelled' => 'danger', default => 'neutral' }">{{ ucfirst($opname->status->value) }}</x-badge>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
        <div class="mt-4">{{ $opnames->links() }}</div>
    @endif
</div>
