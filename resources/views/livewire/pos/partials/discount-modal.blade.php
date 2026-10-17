<x-modal :show="$showDiscountModal" close="showDiscountModal" maxWidth="sm">
    <div class="p-6">
        <h2 class="text-lg font-semibold text-slate-900">Diskon Total (F6)</h2>
        <div class="mt-4 flex gap-2">
            <x-select wire:model="totalDiscountType" :options="['amount' => 'Rupiah', 'percent' => 'Persen']" class="w-32" />
            <x-input type="number" wire:model="totalDiscountValue" placeholder="0" class="flex-1" />
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <x-button type="button" variant="secondary" wire:click="$set('showDiscountModal', false)">Tutup</x-button>
        </div>
    </div>
</x-modal>
