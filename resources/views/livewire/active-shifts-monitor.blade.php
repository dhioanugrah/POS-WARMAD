@php use App\Support\Money; @endphp
<div wire:poll.10s class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900">Shift Aktif Sekarang</h2>
        <span class="flex items-center gap-1.5 text-xs text-slate-400">
            <span class="relative flex size-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span>
            </span>
            Live
        </span>
    </div>

    @if($shifts->isEmpty())
        <p class="text-sm text-slate-400">Tidak ada kasir yang sedang buka shift.</p>
    @else
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($shifts as $row)
                <div class="rounded-lg border border-slate-100 p-4">
                    <div class="flex items-center justify-between">
                        <p class="font-medium text-slate-900">{{ $row['shift']->operator_name ?: $row['shift']->user->name }}</p>
                        <x-badge variant="success">Buka {{ $row['shift']->opened_at->diffForHumans(null, true) }}</x-badge>
                    </div>
                    <p class="text-xs text-slate-400">
                        @if($row['shift']->operator_name)
                            akun {{ $row['shift']->user->name }} &middot;
                        @endif
                        {{ $row['shift']->location->name }} &middot; sejak {{ $row['shift']->opened_at->format('H:i') }}
                    </p>

                    <div class="mt-3 space-y-1 text-sm">
                        <div class="flex justify-between"><span class="text-slate-500">Modal Awal</span><span>{{ Money::format($row['shift']->opening_cash) }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Kas di Laci</span><span class="font-semibold text-emerald-600">{{ Money::format($row['expected_cash']) }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Transaksi</span><span>{{ $row['sales_count'] }} nota &middot; {{ Money::format($row['sales_total']) }}</span></div>
                        @if($row['last_sale_at'])
                            <div class="flex justify-between"><span class="text-slate-500">Terakhir</span><span>{{ \Illuminate\Support\Carbon::parse($row['last_sale_at'])->diffForHumans() }}</span></div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if($recentSales->isNotEmpty())
            <div class="mt-5 border-t border-slate-100 pt-4">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Transaksi Terbaru</p>
                <div class="space-y-1.5">
                    @foreach($recentSales as $sale)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600">{{ $sale->sold_at->format('H:i') }} &middot; {{ $sale->cashier->name }} &middot; <span class="font-mono text-xs text-slate-400">{{ $sale->number }}</span></span>
                            <span class="font-medium text-slate-900">{{ Money::format($sale->total) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>
