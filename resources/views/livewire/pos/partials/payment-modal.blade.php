@php
    use App\Support\Money;
    $paid = $this->paymentTotal;
    $remaining = max(0, $this->estimatedTotal - $paid);
    $change = max(0, $paid - $this->estimatedTotal);
    $isSettled = $paid >= $this->estimatedTotal;
@endphp
<x-modal :show="$showPaymentModal" close="showPaymentModal" maxWidth="lg">
    <div class="p-6" x-data x-on:keydown.enter.window="if({{ $isSettled ? 'true' : 'false' }}) { $wire.pay() }">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Pembayaran</h2>
            <span class="text-2xl font-bold text-slate-900">{{ Money::format($this->estimatedTotal) }}</span>
        </div>

        <div class="mt-4 grid grid-cols-4 gap-2">
            @foreach(['cash' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'debit_card' => 'Kartu'] as $value => $label)
                <button
                    type="button"
                    wire:click="$set('paymentMethod', '{{ $value }}')"
                    class="rounded-lg px-3 py-2 text-sm font-medium {{ $paymentMethod === $value ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                >{{ $label }}</button>
            @endforeach
        </div>

        <div class="mt-4">
            <x-money-input label="Nominal" wire:model="paymentAmount" autofocus />
        </div>

        @if($paymentMethod === 'cash')
            <div class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-6">
                <button type="button" wire:click="quickCash({{ $remaining }})" class="rounded-lg bg-slate-100 py-2 text-xs font-medium hover:bg-slate-200">Uang Pas</button>
                @foreach([10000, 20000, 50000, 100000, 200000] as $amount)
                    <button type="button" wire:click="quickCash({{ $amount }})" class="rounded-lg bg-slate-100 py-2 text-xs font-medium hover:bg-slate-200">{{ Money::format($amount, false) }}</button>
                @endforeach
            </div>
        @endif

        <div class="mt-3">
            <x-button type="button" variant="secondary" size="sm" wire:click="addPaymentRow">+ Tambah Pembayaran (split)</x-button>
        </div>

        @if(!empty($paymentRows))
            <div class="mt-3 space-y-1 rounded-lg bg-slate-50 p-3">
                @foreach($paymentRows as $index => $row)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">{{ ucfirst($row['method']) }}</span>
                        <span class="font-medium">{{ Money::format($row['amount']) }}</span>
                        <button type="button" wire:click="removePaymentRow({{ $index }})" class="text-slate-400 hover:text-rose-600"><x-icon name="trash" class="size-3.5" /></button>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-4 rounded-xl bg-slate-50 p-4">
            @if($isSettled)
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-slate-600">Kembalian</span>
                    <span class="text-3xl font-bold text-emerald-600">{{ Money::format($change) }}</span>
                </div>
            @else
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-slate-600">Sisa Tagihan</span>
                    <span class="text-xl font-bold text-rose-600">{{ Money::format($remaining) }}</span>
                </div>
            @endif
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <x-button type="button" variant="secondary" wire:click="$set('showPaymentModal', false)">Batal</x-button>
            <x-button type="button" wire:click="pay">Proses (Enter)</x-button>
        </div>
    </div>
</x-modal>
