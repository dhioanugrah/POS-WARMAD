<x-modal :show="$showHoldModal" close="showHoldModal" maxWidth="sm">
    <form wire:submit="holdCart" class="p-6">
        <h2 class="text-lg font-semibold text-slate-900">Tahan Transaksi (F7)</h2>
        <div class="mt-4">
            <x-input label="Label" wire:model="holdLabel" autofocus />
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <x-button type="button" variant="secondary" wire:click="$set('showHoldModal', false)">Batal</x-button>
            <x-button type="submit">Tahan</x-button>
        </div>
    </form>
</x-modal>
