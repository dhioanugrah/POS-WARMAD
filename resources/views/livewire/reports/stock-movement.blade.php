@php use App\Support\UnitFormatter; @endphp
<div>
    <x-page-header title="Laporan Mutasi Stok">
        <x-slot:actions>
            <a href="{{ route('reports.index') }}" wire:navigate><x-button variant="secondary">Kembali</x-button></a>
        </x-slot:actions>
    </x-page-header>

    @include('livewire.reports.partials.date-filter')
    <div class="mb-4 max-w-xs">
        <x-select wire:model.live="locationFilter" placeholder="Semua Lokasi" :options="$locations" />
    </div>

    @if($rows->isEmpty())
        <x-empty-state title="Tidak ada mutasi stok pada periode ini" />
    @else
        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Barang</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Awal</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Masuk</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Keluar</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Akhir</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($rows as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $row['product']->name }}</td>
                        <td class="px-4 py-3 text-right">{{ UnitFormatter::formatQty($row['opening']) }}</td>
                        <td class="px-4 py-3 text-right text-emerald-600">{{ UnitFormatter::formatQty($row['in']) }}</td>
                        <td class="px-4 py-3 text-right text-rose-600">{{ UnitFormatter::formatQty($row['out']) }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ UnitFormatter::breakdown($row['product'], $row['closing']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    @endif
</div>
