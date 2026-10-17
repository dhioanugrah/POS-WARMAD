@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Laporan Kas Masuk/Keluar">
        <x-slot:actions>
            <a href="{{ route('reports.index') }}" wire:navigate><x-button variant="secondary">Kembali</x-button></a>
        </x-slot:actions>
    </x-page-header>

    @include('livewire.reports.partials.date-filter')

    <div class="mb-4 grid grid-cols-2 gap-3 sm:w-96">
        <x-stat-card label="Total Masuk" :value="Money::format($totalIn)" variant="success" />
        <x-stat-card label="Total Keluar" :value="Money::format($totalOut)" variant="danger" />
    </div>

    @if($byCategory->isEmpty())
        <x-empty-state title="Tidak ada transaksi kas pada periode ini" />
    @else
        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Kategori</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Tipe</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($byCategory as $row)
                    <tr>
                        <td class="px-4 py-3">{{ $row['name'] }}</td>
                        <td class="px-4 py-3"><x-badge :variant="$row['type'] === 'in' ? 'success' : 'danger'">{{ $row['type'] === 'in' ? 'Masuk' : 'Keluar' }}</x-badge></td>
                        <td class="px-4 py-3 text-right">{{ Money::format($row['total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    @endif
</div>
