@php use App\Support\Money; use App\Services\ShiftService; @endphp
<x-modal :show="$showCloseShiftModal" close="showCloseShiftModal" maxWidth="sm">
    @if($this->shift)
        @php
            $expected = app(ShiftService::class)->expectedCash($this->shift);
        @endphp
        <form wire:submit="closeShift" class="p-6">
            <h2 class="text-lg font-semibold text-slate-900">Tutup Shift</h2>
            <div class="mt-4 space-y-1 rounded-lg bg-slate-50 p-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Modal Awal</span><span>{{ Money::format($this->shift->opening_cash) }}</span></div>
                <div class="flex justify-between font-medium"><span class="text-slate-600">Uang Seharusnya</span><span>{{ Money::format($expected) }}</span></div>
            </div>
            <div class="mt-4">
                <x-money-input label="Uang Fisik di Laci" wire:model="countedCash" autofocus />
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <x-button type="button" variant="secondary" wire:click="$set('showCloseShiftModal', false)">Batal</x-button>
                <x-button type="submit" variant="danger">Tutup Shift</x-button>
            </div>
        </form>
    @endif
</x-modal>
