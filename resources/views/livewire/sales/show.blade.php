@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Nota {{ $sale->number }}">
        <x-slot:actions>
            <a href="{{ route('pos.receipt', ['sale' => $sale->id, 'copy' => 1]) }}" target="_blank">
                <x-button variant="secondary"><x-icon name="printer" class="size-4" /> Cetak Ulang</x-button>
            </a>
            <a href="{{ route('sales.index') }}" wire:navigate><x-button variant="secondary">Kembali</x-button></a>
        </x-slot:actions>
    </x-page-header>

    @if($sale->status->value === 'void')
        <div class="mb-4 rounded-xl bg-rose-50 p-4 text-sm text-rose-700 ring-1 ring-rose-200">
            <p class="font-semibold">Transaksi ini di-VOID</p>
            <p>Oleh: {{ $sale->voidedBy?->name }} pada {{ $sale->voided_at?->format('d M Y H:i') }}</p>
            <p>Alasan: {{ $sale->void_reason }}</p>
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
            <div class="mb-4 grid grid-cols-2 gap-2 text-sm text-slate-600">
                <div>Tanggal: {{ $sale->sold_at->format('d M Y H:i') }}</div>
                <div>Kasir: {{ $sale->cashier->name }}</div>
                <div>Pelanggan: {{ $sale->customer->name ?? 'Umum' }}</div>
                <div>Lokasi: {{ $sale->location->name }}</div>
            </div>

            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="py-2">Barang</th>
                        <th class="py-2 text-right">Qty</th>
                        <th class="py-2 text-right">Harga</th>
                        <th class="py-2 text-right">Diskon</th>
                        <th class="py-2 text-right">Subtotal</th>
                        @can('products.view-cost')
                            <th class="py-2 text-right">HPP</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($sale->items as $item)
                        <tr>
                            <td class="py-2">{{ $item->product_name }}</td>
                            <td class="py-2 text-right">{{ rtrim(rtrim(number_format($item->qty, 3), '0'), '.') }} {{ $item->unit_name }}</td>
                            <td class="py-2 text-right">{{ Money::format($item->price) }}</td>
                            <td class="py-2 text-right">{{ Money::format($item->discount_amount) }}</td>
                            <td class="py-2 text-right">{{ Money::format($item->subtotal) }}</td>
                            @can('products.view-cost')
                                <td class="py-2 text-right text-slate-500">{{ Money::format($item->unit_cost * $item->qty) }}</td>
                            @endcan
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span>{{ Money::format($sale->subtotal) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Diskon</span><span>{{ Money::format($sale->discount_amount) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Pajak</span><span>{{ Money::format($sale->tax_amount) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Pembulatan</span><span>{{ Money::format($sale->rounding_amount) }}</span></div>
                    <div class="flex justify-between border-t border-slate-100 pt-1 font-semibold"><span>Total</span><span>{{ Money::format($sale->total) }}</span></div>
                    @can('products.view-cost')
                        <div class="flex justify-between border-t border-slate-100 pt-1 text-emerald-600"><span>Laba</span><span>{{ Money::format($sale->total - $sale->cost_total) }}</span></div>
                    @endcan
                </div>
                <div class="mt-3 space-y-1 border-t border-slate-100 pt-3 text-sm">
                    @foreach($sale->payments as $payment)
                        <div class="flex justify-between"><span class="text-slate-500">{{ ucfirst($payment->method->value) }}</span><span>{{ Money::format($payment->amount) }}</span></div>
                    @endforeach
                    <div class="flex justify-between"><span class="text-slate-500">Kembalian</span><span>{{ Money::format($sale->change_amount) }}</span></div>
                </div>
            </div>

            @can('sales.void')
                @if($sale->status->value !== 'void')
                    <div x-data="{ reason: '' }" class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-900/5">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Void Transaksi</label>
                        <input x-model="reason" type="text" placeholder="Alasan" class="mb-2 block w-full rounded-lg border-0 py-2 px-3 text-sm ring-1 ring-slate-300">
                        <x-button variant="danger" class="w-full" x-on:click="if(reason) $wire.void(reason)">Void</x-button>
                    </div>
                @endif
            @endcan
        </div>
    </div>
</div>
