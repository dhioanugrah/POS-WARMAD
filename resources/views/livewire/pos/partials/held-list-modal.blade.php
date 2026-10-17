@php use App\Support\Money; @endphp
<x-modal :show="$showHeldListModal" close="showHeldListModal" maxWidth="md">
    <div class="p-6">
        <h2 class="text-lg font-semibold text-slate-900">Daftar Tahan (F8)</h2>
        <div class="mt-4 max-h-80 space-y-2 overflow-y-auto">
            @forelse($heldCarts as $held)
                <div class="flex items-center justify-between rounded-lg bg-slate-50 p-3">
                    <div>
                        <p class="font-medium text-slate-900">{{ $held->label }}</p>
                        <p class="text-xs text-slate-400">{{ count($held->payload['items'] ?? []) }} item &middot; {{ $held->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex gap-2">
                        <x-button type="button" size="sm" wire:click="resumeHeldCart({{ $held->id }})">Panggil</x-button>
                        <button type="button" wire:click="deleteHeldCart({{ $held->id }})" wire:confirm="Hapus keranjang tahan ini?" class="text-slate-400 hover:text-rose-600"><x-icon name="trash" class="size-4" /></button>
                    </div>
                </div>
            @empty
                <p class="text-center text-sm text-slate-400">Tidak ada transaksi yang ditahan.</p>
            @endforelse
        </div>
        <div class="mt-4 flex justify-end">
            <x-button type="button" variant="secondary" wire:click="$set('showHeldListModal', false)">Tutup</x-button>
        </div>
    </div>
</x-modal>
