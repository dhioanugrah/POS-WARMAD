@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Laporan Shift">
        <x-slot:actions>
            <a href="{{ route('reports.index') }}" wire:navigate><x-button variant="secondary">Kembali</x-button></a>
        </x-slot:actions>
    </x-page-header>

    @include('livewire.reports.partials.date-filter')

    @if($shifts->isEmpty())
        <x-empty-state title="Tidak ada shift pada periode ini" />
    @else
        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Kasir</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Dibuka</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Modal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Per Metode</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Seharusnya</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Fisik</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Selisih</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($shifts as $shift)
                    <tr>
                        <td class="px-4 py-3">{{ $shift->user->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $shift->opened_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">{{ Money::format($shift->opening_cash) }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            @foreach($breakdowns[$shift->id] ?? [] as $method => $amount)
                                {{ ucfirst($method) }}: {{ Money::format($amount) }}<br>
                            @endforeach
                        </td>
                        <td class="px-4 py-3 text-right">{{ $shift->expected_cash !== null ? Money::format($shift->expected_cash) : '-' }}</td>
                        <td class="px-4 py-3 text-right">{{ $shift->counted_cash !== null ? Money::format($shift->counted_cash) : '-' }}</td>
                        <td class="px-4 py-3 text-right {{ (float) $shift->difference < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                            {{ $shift->difference !== null ? Money::format($shift->difference) : '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
        <div class="mt-4">{{ $shifts->links() }}</div>
    @endif
</div>
