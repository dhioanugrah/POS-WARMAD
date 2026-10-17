<div>
    <x-page-header title="Laporan" description="Semua laporan mengecualikan transaksi yang di-void." />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($reports as $report)
            <a href="{{ route($report['route']) }}" wire:navigate class="flex items-start gap-3 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5 hover:ring-emerald-300">
                <span class="flex size-10 flex-shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <x-icon :name="$report['icon']" class="size-5" />
                </span>
                <div>
                    <p class="font-medium text-slate-900">{{ $report['label'] }}</p>
                    <p class="mt-0.5 text-sm text-slate-500">{{ $report['description'] }}</p>
                </div>
            </a>
        @endforeach
    </div>
</div>
