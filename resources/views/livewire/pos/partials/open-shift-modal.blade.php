<x-modal :show="$showOpenShiftModal" close="showOpenShiftModal" :dismissable="false" maxWidth="sm">
    <div class="flex items-start justify-between p-6 pb-0">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Buka Shift</h2>
            <p class="mt-1 text-sm text-slate-500">Masukkan modal awal kas di laci sebelum mulai transaksi.</p>
        </div>
        <p class="flex-shrink-0 text-sm text-slate-500">
            {{ auth()->user()->name }} &middot;
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('open-shift-logout-form').submit();" class="font-medium text-rose-600 hover:underline">Keluar</a>
        </p>
        <form id="open-shift-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>
    </div>
    <form wire:submit="openShift" class="p-6">
        <div class="mt-4">
            <x-money-input label="Modal Awal" wire:model="openingCash" />
        </div>
        <div class="mt-6 flex justify-end">
            <x-button type="submit">Buka Shift</x-button>
        </div>
    </form>
</x-modal>
