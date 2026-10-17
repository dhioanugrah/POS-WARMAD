@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Laporan Penjualan">
        <x-slot:actions>
            <a href="{{ route('reports.index') }}" wire:navigate><x-button variant="secondary">Kembali</x-button></a>
        </x-slot:actions>
    </x-page-header>

    @include('livewire.reports.partials.date-filter')
    <div class="mb-4 max-w-xs">
        <x-select wire:model.live="locationFilter" placeholder="Semua Lokasi" :options="$locations" />
    </div>

    <div class="mb-4 grid grid-cols-2 gap-3 sm:w-96">
        <x-stat-card label="Jumlah Nota" :value="$count" />
        <x-stat-card label="Total Omzet" :value="Money::format($total)" variant="success" />
    </div>

    <div class="mb-4 flex gap-1 rounded-lg bg-slate-100 p-1 max-w-lg">
        @foreach(['harian' => 'Harian', 'kasir' => 'Per Kasir', 'metode' => 'Per Metode', 'pelanggan' => 'Per Pelanggan', 'nota' => 'Per Nota'] as $key => $label)
            <button type="button" wire:click="$set('tab', '{{ $key }}')" class="flex-1 rounded-md px-3 py-1.5 text-xs font-medium {{ $tab === $key ? 'bg-white shadow text-slate-900' : 'text-slate-600' }}">{{ $label }}</button>
        @endforeach
    </div>

    @if($tab === 'harian')
        <x-table>
            <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Tanggal</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Jumlah Nota</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Omzet</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($byDay as $row)
                    <tr><td class="px-4 py-3">{{ \Carbon\Carbon::parse($row['day'])->translatedFormat('d M Y') }}</td><td class="px-4 py-3 text-right">{{ $row['count'] }}</td><td class="px-4 py-3 text-right">{{ Money::format($row['total']) }}</td></tr>
                @endforeach
            </tbody>
        </x-table>
    @elseif($tab === 'kasir')
        <x-table>
            <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Kasir</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Jumlah Nota</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Omzet</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($byCashier as $row)
                    <tr><td class="px-4 py-3">{{ $row['name'] }}</td><td class="px-4 py-3 text-right">{{ $row['count'] }}</td><td class="px-4 py-3 text-right">{{ Money::format($row['total']) }}</td></tr>
                @endforeach
            </tbody>
        </x-table>
    @elseif($tab === 'metode')
        <x-table>
            <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Metode</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Jumlah</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Total</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($byMethod as $row)
                    <tr><td class="px-4 py-3">{{ ucfirst($row['method']) }}</td><td class="px-4 py-3 text-right">{{ $row['count'] }}</td><td class="px-4 py-3 text-right">{{ Money::format($row['total']) }}</td></tr>
                @endforeach
            </tbody>
        </x-table>
    @elseif($tab === 'pelanggan')
        <x-table>
            <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Pelanggan</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Jumlah Nota</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Omzet</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($byCustomer as $row)
                    <tr><td class="px-4 py-3">{{ $row['name'] }}</td><td class="px-4 py-3 text-right">{{ $row['count'] }}</td><td class="px-4 py-3 text-right">{{ Money::format($row['total']) }}</td></tr>
                @endforeach
            </tbody>
        </x-table>
    @else
        <x-table>
            <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">No. Nota</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Tanggal</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Kasir</th><th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Total</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($sales as $sale)
                    <tr class="cursor-pointer hover:bg-slate-50" onclick="window.location='{{ route('sales.show', $sale) }}'">
                        <td class="px-4 py-3 font-mono text-xs">{{ $sale->number }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $sale->sold_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $sale->cashier->name }}</td>
                        <td class="px-4 py-3 text-right">{{ Money::format($sale->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    @endif
</div>
