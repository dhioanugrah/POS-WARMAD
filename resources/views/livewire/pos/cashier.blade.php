@php use App\Support\Money; @endphp
<div
    x-data="{}"
    x-on:focus-search.window="$refs.searchInput?.focus()"
    x-on:print-receipt.window="
        let f = document.getElementById('receipt-frame');
        f.src = '/pos/receipt/' + $event.detail.saleId + '?t=' + Date.now() + ($event.detail.copy ? '&copy=1' : '');
    "
    x-on:keydown.window.f2.prevent="$refs.searchInput?.focus()"
    x-on:keydown.window.f4.prevent="$dispatch('open-customer-picker')"
    x-on:keydown.window.f6.prevent="$wire.set('showDiscountModal', true)"
    x-on:keydown.window.f7.prevent="$wire.openHold()"
    x-on:keydown.window.f8.prevent="$wire.set('showHeldListModal', true)"
    x-on:keydown.window.f9.prevent="$wire.openPayment()"
    x-on:keydown.window.f10.prevent="$wire.reprintLast()"
    x-on:keydown.window.escape="$wire.set('showPaymentModal', false); $wire.set('showDiscountModal', false); $wire.set('showHeldListModal', false); $wire.set('showHistoryModal', false); $wire.set('showHoldModal', false); $wire.set('showCashDrawerModal', false)"
    class="flex h-screen flex-col bg-slate-100"
>
    {{-- Topbar --}}
    <div class="flex h-14 flex-shrink-0 items-center gap-4 border-b border-slate-200 bg-white px-4">
        <span class="font-semibold text-slate-900">{{ setting('store.name', config('app.name')) }}</span>
        <span class="text-sm text-slate-400">|</span>
        <span class="text-sm text-slate-600">Kasir: {{ $this->shift?->operator_name ?: auth()->user()->name }}</span>
        @if($this->shift)
            <span class="text-sm text-slate-500">Shift sejak {{ $this->shift->opened_at->format('H:i') }}</span>
        @endif
        <div class="flex-1"></div>
        <div class="flex items-center gap-2">
            <button wire:click="$set('showHistoryModal', true)" class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Riwayat</button>
            <button wire:click="$set('showHeldListModal', true)" class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Tahan ({{ $heldCarts->count() }})</button>
            <button wire:click="openCashDrawer('out')" class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Kas Laci</button>
            @if($this->shift)
                <button wire:click="openCloseShift" class="rounded-lg bg-rose-50 px-3 py-1.5 text-sm font-medium text-rose-600 hover:bg-rose-100">Tutup Shift</button>
            @endif
            <a href="{{ route('dashboard') }}" wire:navigate class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Menu</a>
        </div>
    </div>

    {{-- Main --}}
    <div class="flex flex-1 overflow-hidden">
        {{-- Left: search + results --}}
        <div class="flex w-1/2 flex-col border-r border-slate-200 p-4">
            <div class="relative">
                <input
                    x-ref="searchInput"
                    type="text"
                    wire:model.live.debounce.150ms="search"
                    wire:keydown.enter="handleEnter"
                    wire:keydown.arrow-down.prevent="arrowDown"
                    wire:keydown.arrow-up.prevent="arrowUp"
                    autofocus
                    placeholder="Scan barcode / cari nama... (F2)"
                    class="block w-full rounded-xl border-0 py-3 px-4 text-lg shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600"
                >
            </div>

            <div class="mt-3 flex-1 overflow-y-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5">
                @forelse($searchResultsList as $index => $unit)
                    <button
                        type="button"
                        wire:click="addToCart({{ $unit->id }})"
                        class="flex w-full items-center justify-between border-b border-slate-100 px-4 py-3 text-left last:border-0 {{ $index === $highlightedIndex ? 'bg-emerald-50' : 'hover:bg-slate-50' }}"
                    >
                        <div>
                            <p class="font-medium text-slate-900">{{ $unit->product->name }}</p>
                            <p class="text-xs text-slate-400">{{ $unit->product->code }} &middot; {{ $unit->unit_name }}</p>
                        </div>
                        <span class="font-semibold text-slate-700">{{ Money::format($unit->price_sell) }}</span>
                    </button>
                @empty
                    @if(trim($search) === '' && $barcodeProducts->isNotEmpty())
                        {{-- Item cards: pick an item, then one of its barcodes; the barcode is
                             typed into the scan box and submitted just like a scanner would. --}}
                        <div
                            x-data="{
                                selected: null,
                                pick(barcode, remaining) {
                                    if (remaining <= 1) this.selected = null;
                                    $wire.search = barcode;
                                    $wire.handleEnter();
                                },
                            }"
                            class="p-3"
                        >
                            <div x-show="selected === null" class="grid grid-cols-2 gap-3 lg:grid-cols-3">
                                @foreach($barcodeProducts as $card)
                                    <button type="button" wire:key="card-{{ $card['product_id'] }}" x-on:click="selected = {{ $card['product_id'] }}" class="flex flex-col overflow-hidden rounded-xl bg-white text-left ring-1 ring-slate-200 transition hover:ring-2 hover:ring-emerald-500">
                                        @if($card['image_path'])
                                            <img src="{{ Storage::url($card['image_path']) }}" alt="" class="aspect-square w-full object-cover">
                                        @else
                                            <div class="flex aspect-square w-full items-center justify-center bg-slate-100 text-slate-300"><x-icon name="cube" class="size-10" /></div>
                                        @endif
                                        <div class="flex flex-1 flex-col gap-1 p-3">
                                            <p class="line-clamp-2 text-sm font-medium text-slate-900">{{ $card['name'] }}</p>
                                            <p class="text-sm font-semibold text-emerald-600">{{ Money::format($card['price']) }}</p>
                                            <div class="mt-auto flex flex-wrap items-center gap-1 pt-1">
                                                <x-badge :variant="$card['stock'] > 0 ? 'neutral' : 'warning'">Stok {{ \App\Support\UnitFormatter::formatQty($card['stock']) }} {{ $card['base_unit'] }}</x-badge>
                                                <x-badge variant="info">{{ count($card['barcodes']) }} barcode</x-badge>
                                            </div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>

                            @foreach($barcodeProducts as $card)
                                <div wire:key="card-barcodes-{{ $card['product_id'] }}" x-show="selected === {{ $card['product_id'] }}" x-cloak>
                                    <button type="button" x-on:click="selected = null" class="mb-3 flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900">
                                        <x-icon name="chevron-left" class="size-4" /> Semua barang
                                    </button>
                                    <p class="font-semibold text-slate-900">{{ $card['name'] }}</p>
                                    <p class="mb-3 text-xs text-slate-500">Klik barcode untuk memasukkannya seperti hasil scan.</p>
                                    <div class="divide-y divide-slate-100 rounded-lg ring-1 ring-slate-200">
                                        @foreach($card['barcodes'] as $b)
                                            <button type="button" wire:key="card-barcode-{{ $b['barcode'] }}" x-on:click="pick(@js($b['barcode']), {{ count($card['barcodes']) }})" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left hover:bg-emerald-50">
                                                <span>
                                                    <span class="block font-mono text-slate-900">{{ $b['barcode'] }}</span>
                                                    <span class="block text-xs text-slate-500">{{ $b['unit_name'] }}</span>
                                                </span>
                                                <span class="text-sm font-semibold text-slate-700">{{ Money::format($b['price']) }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex h-full items-center justify-center p-8 text-center text-sm text-slate-400">
                            Ketik nama/kode barang atau scan barcode untuk mulai.
                        </div>
                    @endif
                @endforelse
            </div>
        </div>

        {{-- Right: cart --}}
        <div class="flex w-1/2 flex-col p-4">
            <div class="mb-3 flex items-center justify-between">
                <livewire:pos.customer-picker />
                <button wire:click="clearCart" wire:confirm="Kosongkan keranjang?" class="text-sm text-slate-400 hover:text-rose-600">Kosongkan</button>
            </div>

            <div class="flex-1 overflow-y-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5">
                @forelse($cart as $key => $row)
                    <div wire:key="cart-{{ $key }}" class="border-b border-slate-100 p-3 last:border-0">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-slate-900">{{ $row['name'] }}</p>
                                <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                                    @if(count($row['available_units']) > 1)
                                        <select wire:change="changeUnit({{ $key }}, $event.target.value)" class="rounded border-0 bg-slate-100 py-1 pl-2 pr-6 text-xs">
                                            @foreach($row['available_units'] as $u)
                                                <option value="{{ $u['id'] }}" @selected($u['id'] === $key)>{{ $u['unit_name'] }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <span>{{ $row['unit_name'] }}</span>
                                    @endif
                                    <span>@ {{ Money::format($row['price']) }}</span>
                                    @if($row['discount_value'])
                                        <x-badge variant="info">disc {{ $row['discount_type'] === 'percent' ? $row['discount_value'].'%' : Money::format($row['discount_value']) }}</x-badge>
                                    @endif
                                </div>
                                @if(!$row['is_service'])
                                    @if($row['stock_qty'] <= 0)
                                        <x-badge variant="danger" class="mt-1">Stok habis</x-badge>
                                    @elseif($row['stock_qty'] <= $row['min_stock'])
                                        <x-badge variant="warning" class="mt-1">Stok menipis: {{ rtrim(rtrim(number_format($row['stock_qty'], 3), '0'), '.') }}</x-badge>
                                    @endif
                                @endif
                            </div>
                            <div class="flex items-center gap-1">
                                <button wire:click="updateQty({{ $key }}, {{ $row['qty'] - 1 }})" class="flex size-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200">−</button>
                                <input type="text" wire:change="updateQty({{ $key }}, $event.target.value)" value="{{ rtrim(rtrim(number_format($row['qty'], 3, '.', ''), '0'), '.') }}" class="w-14 rounded-lg border-0 bg-slate-50 py-1 text-center text-sm ring-1 ring-slate-200">
                                <button wire:click="updateQty({{ $key }}, {{ $row['qty'] + 1 }})" class="flex size-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200">+</button>
                                <button wire:click="removeRow({{ $key }})" class="ml-1 text-slate-400 hover:text-rose-600"><x-icon name="trash" class="size-4" /></button>
                            </div>
                        </div>
                        <div class="mt-1 flex items-center justify-between">
                            <button
                                type="button"
                                x-data
                                x-on:click="
                                    let val = prompt('Diskon item (isi angka lalu %, contoh 10% atau 500):', '');
                                    if (val !== null) {
                                        let type = val.trim().endsWith('%') ? 'percent' : 'amount';
                                        let num = parseFloat(val.replace('%','')) || 0;
                                        $wire.setItemDiscount({{ $key }}, type, num);
                                    }
                                "
                                class="text-xs text-emerald-600 hover:underline"
                            >+ Diskon</button>
                            <span class="font-semibold text-slate-900">{{ Money::format($this->rowSubtotal($row)) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="flex h-full items-center justify-center p-8 text-center text-sm text-slate-400">Keranjang kosong.</div>
                @endforelse
            </div>

            <div class="mt-3 space-y-1 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-900/5">
                <div class="flex justify-between text-sm text-slate-600">
                    <span>Subtotal</span><span>{{ Money::format($this->subtotal) }}</span>
                </div>
                <button wire:click="$set('showDiscountModal', true)" class="flex w-full justify-between text-sm text-slate-600 hover:text-emerald-600">
                    <span>Diskon (F6)</span><span>{{ Money::format($this->totalDiscountAmount) }}</span>
                </button>
                <div class="flex items-baseline justify-between border-t border-slate-100 pt-2">
                    <span class="text-base font-semibold text-slate-900">TOTAL</span>
                    <span class="text-2xl font-bold text-emerald-600">{{ Money::format($this->estimatedTotal) }}</span>
                </div>
                <div class="flex gap-2 pt-2">
                    <x-button variant="secondary" size="touch" class="flex-1" wire:click="openHold">Tahan (F7)</x-button>
                    <x-button size="touch" class="flex-[2]" wire:click="openPayment">BAYAR (F9)</x-button>
                </div>
            </div>
        </div>
    </div>

    {{-- Shortcut legend --}}
    <div class="flex h-9 flex-shrink-0 items-center justify-center gap-4 border-t border-slate-200 bg-white text-xs text-slate-400">
        <span>F2 Cari</span><span>F4 Pelanggan</span><span>F6 Diskon</span><span>F7 Tahan</span><span>F8 Daftar Tahan</span><span>F9 Bayar</span><span>F10 Cetak Ulang</span><span>Esc Batal</span>
    </div>

    <iframe id="receipt-frame" class="hidden" onload="if(this.src && this.src !== 'about:blank'){ this.contentWindow.focus(); this.contentWindow.print(); }"></iframe>

    @include('livewire.pos.partials.open-shift-modal')
    @include('livewire.pos.partials.discount-modal')
    @include('livewire.pos.partials.payment-modal')
    @include('livewire.pos.partials.hold-modal')
    @include('livewire.pos.partials.held-list-modal')
    @include('livewire.pos.partials.history-modal')
    @include('livewire.pos.partials.close-shift-modal')
    @include('livewire.pos.partials.cash-drawer-modal')
</div>
