@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Laporan Penjualan per Barang">
        <x-slot:actions>
            <a href="{{ route('reports.index') }}" wire:navigate><x-button variant="secondary">Kembali</x-button></a>
        </x-slot:actions>
    </x-page-header>

    @include('livewire.reports.partials.date-filter')

    @if($rows->isEmpty())
        <x-empty-state title="Tidak ada data penjualan pada periode ini" />
    @else
        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Barang</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Qty</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Omzet</th>
                    @if($canViewCost)
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">HPP</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Laba</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Margin</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($rows as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $row->product_name }}</td>
                        <td class="px-4 py-3 text-right">{{ rtrim(rtrim(number_format($row->qty, 3), '0'), '.') }}</td>
                        <td class="px-4 py-3 text-right">{{ Money::format($row->revenue) }}</td>
                        @if($canViewCost)
                            <td class="px-4 py-3 text-right text-slate-600">{{ Money::format($row->cost) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-600">{{ Money::format($row->profit) }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ $row->margin }}%</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    @endif
</div>
