@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Dashboard" description="Ringkasan toko hari ini, {{ now()->translatedFormat('d F Y') }}." />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <x-stat-card
            label="Omzet Hari Ini"
            :value="Money::format($todayTotal)"
            variant="success"
            :hint="$changePercent === null ? null : (($changePercent >= 0 ? '+' : '').$changePercent.'% dari kemarin')"
        />
        <x-stat-card label="Jumlah Transaksi" :value="$todayCount" />
        <x-stat-card label="Rata-rata / Transaksi" :value="Money::format($avgPerTransaction)" />
        <x-stat-card label="Laba Kotor Hari Ini" :value="Money::format($grossProfit)" variant="info" />
        <x-stat-card label="Kas di Laci (Shift Aktif)" :value="Money::format($cashInDrawer)" variant="warning" />
    </div>

    @if($lowStockCount > 0)
        <a href="{{ route('stock.minimum') }}" wire:navigate class="mt-4 flex items-center gap-2 rounded-xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200 hover:bg-amber-100">
            <x-icon name="exclamation-triangle" class="size-5" />
            <span><strong>{{ $lowStockCount }} barang</strong> stoknya di bawah atau sama dengan minimum. Klik untuk lihat & buat draft pembelian.</span>
        </a>
    @endif

    @can('cash.manage')
        <div class="mt-6">
            <livewire:active-shifts-monitor />
        </div>
    @endcan

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 lg:col-span-2">
            <h2 class="mb-4 text-sm font-semibold text-slate-900">Omzet 30 Hari Terakhir</h2>
            <div
                x-data="{
                    init() {
                        new Chart(this.$refs.canvas, {
                            type: 'line',
                            data: {
                                labels: @js($chartLabels),
                                datasets: [{
                                    label: 'Omzet',
                                    data: @js($chartData),
                                    borderColor: '#059669',
                                    backgroundColor: 'rgba(5,150,105,0.1)',
                                    fill: true,
                                    tension: 0.3,
                                    pointRadius: 0,
                                }],
                            },
                            options: {
                                plugins: { legend: { display: false } },
                                scales: { y: { beginAtZero: true } },
                            },
                        });
                    }
                }"
                style="height: 260px"
            >
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
            <h2 class="mb-4 text-sm font-semibold text-slate-900">10 Barang Terlaris (7 hari)</h2>
            @if($topProducts->isEmpty())
                <p class="text-sm text-slate-400">Belum ada data penjualan.</p>
            @else
                <ol class="space-y-2 text-sm">
                    @foreach($topProducts as $i => $product)
                        <li class="flex items-center justify-between">
                            <span class="text-slate-600">{{ $i + 1 }}. {{ $product->product_name }}</span>
                            <span class="font-medium text-slate-900">{{ Money::format($product->revenue) }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>

    @if($todayCount === 0)
        <div class="mt-6">
            <x-empty-state
                title="Belum ada transaksi hari ini"
                description="Data omzet, laba, dan grafik akan muncul di sini setelah ada transaksi kasir."
            />
        </div>
    @endif
</div>
