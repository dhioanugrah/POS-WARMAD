@props(['options', 'action' => 'pickBarcode', 'title' => 'Pilih barcode (yang belum dimasukkan)'])

<div {{ $attributes->class('divide-y divide-slate-100') }}>
    <p class="px-3 py-2 text-xs font-medium text-slate-500">{{ $title }}</p>
    @forelse($options as $option)
        <button type="button" wire:key="barcode-option-{{ $option['barcode'] }}" wire:click="{{ $action }}(@js($option['barcode']))" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-slate-50">
            <span class="min-w-0">
                <span class="block truncate font-mono text-sm text-slate-900">{{ $option['barcode'] }}</span>
                <span class="block truncate text-xs text-slate-500">{{ $option['product_name'] }} ({{ $option['unit_name'] }})</span>
            </span>
            <x-badge :variant="$option['stock'] > 0 ? 'neutral' : 'warning'" class="flex-shrink-0">Stok {{ \App\Support\UnitFormatter::formatQty($option['stock']) }} {{ $option['base_unit'] }}</x-badge>
        </button>
    @empty
        <p class="px-3 py-3 text-sm text-slate-400">Tidak ada barcode yang tersisa.</p>
    @endforelse
</div>
