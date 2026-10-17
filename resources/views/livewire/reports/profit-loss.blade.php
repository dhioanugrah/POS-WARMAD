@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Laba Rugi Sederhana">
        <x-slot:actions>
            <a href="{{ route('reports.index') }}" wire:navigate><x-button variant="secondary">Kembali</x-button></a>
        </x-slot:actions>
    </x-page-header>

    @include('livewire.reports.partials.date-filter')

    <div class="max-w-md rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
        <div class="space-y-3 text-sm">
            <div class="flex justify-between"><span class="text-slate-600">Penjualan Bersih</span><span class="font-medium">{{ Money::format($netSales) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-600">HPP (Harga Pokok Penjualan)</span><span class="font-medium">-{{ Money::format($cogs) }}</span></div>
            <div class="flex justify-between border-t border-slate-100 pt-3 font-semibold"><span>Laba Kotor</span><span>{{ Money::format($grossProfit) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-600">Biaya Operasional</span><span class="font-medium">-{{ Money::format($expenses) }}</span></div>
            <div class="flex justify-between border-t border-slate-200 pt-3 text-lg font-bold {{ $netProfit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                <span>Laba Bersih</span><span>{{ Money::format($netProfit) }}</span>
            </div>
        </div>
    </div>
</div>
