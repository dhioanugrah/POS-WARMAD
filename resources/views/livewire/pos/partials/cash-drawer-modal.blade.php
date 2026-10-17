<x-modal :show="$showCashDrawerModal" close="showCashDrawerModal" maxWidth="sm">
    <form wire:submit="submitCashDrawer" class="p-6">
        <h2 class="text-lg font-semibold text-slate-900">{{ $cashDrawerType === 'in' ? 'Kas Masuk Laci' : 'Kas Keluar Laci' }}</h2>
        <div class="mt-4 space-y-4">
            <div class="flex gap-2">
                <button type="button" wire:click="$set('cashDrawerType', 'out')" class="flex-1 rounded-lg py-2 text-sm font-medium {{ $cashDrawerType === 'out' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-600' }}">Keluar</button>
                <button type="button" wire:click="$set('cashDrawerType', 'in')" class="flex-1 rounded-lg py-2 text-sm font-medium {{ $cashDrawerType === 'in' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600' }}">Masuk</button>
            </div>
            <x-select label="Kategori" wire:model="cashDrawerCategoryId" placeholder="- Pilih Kategori -" :options="$cashCategories" />
            <x-money-input label="Jumlah" wire:model="cashDrawerAmount" />
            <x-input label="Keterangan" wire:model="cashDrawerDescription" placeholder="Contoh: Beli galon" />
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <x-button type="button" variant="secondary" wire:click="$set('showCashDrawerModal', false)">Batal</x-button>
            <x-button type="submit">Simpan</x-button>
        </div>
    </form>
</x-modal>
