<div x-data="{ tab: 'profil' }">
    <x-page-header title="Pengaturan" description="Profil toko, struk, kasir, dan penomoran dokumen.">
        <x-slot:actions>
            <x-button wire:click="save" wire:loading.attr="disabled">Simpan Perubahan</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 flex gap-1 overflow-x-auto rounded-lg bg-slate-100 p-1">
        @foreach(['profil' => 'Profil Toko', 'struk' => 'Struk', 'kasir' => 'Kasir', 'penomoran' => 'Penomoran'] as $key => $label)
            <button
                type="button"
                x-on:click="tab = '{{ $key }}'"
                x-bind:class="tab === '{{ $key }}' ? 'bg-white shadow text-slate-900' : 'text-slate-600 hover:text-slate-900'"
                class="flex-1 whitespace-nowrap rounded-md px-4 py-2 text-sm font-medium transition"
            >{{ $label }}</button>
        @endforeach
    </div>

    <form wire:submit="save" class="space-y-6">
        <div x-show="tab === 'profil'" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input label="Nama Toko" wire:model="storeName" />
                <x-input label="Telepon" wire:model="storePhone" />
            </div>
            <x-input label="Alamat" wire:model="storeAddress" />
            <x-input label="NPWP (opsional)" wire:model="storeTaxId" />

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Logo Toko</label>
                <div class="flex items-center gap-4">
                    @if($newLogo)
                        <img src="{{ $newLogo->temporaryUrl() }}" class="size-16 rounded-lg object-cover ring-1 ring-slate-200">
                    @elseif($logoPath)
                        <img src="{{ Storage::url($logoPath) }}" class="size-16 rounded-lg object-cover ring-1 ring-slate-200">
                    @else
                        <div class="flex size-16 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                            <x-icon name="cube" class="size-6" />
                        </div>
                    @endif
                    <input type="file" wire:model="newLogo" accept="image/*" class="text-sm text-slate-600" />
                    @if($logoPath)
                        <x-button type="button" variant="ghost" size="sm" wire:click="removeLogo">Hapus</x-button>
                    @endif
                </div>
                @error('newLogo') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div x-show="tab === 'struk'" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-select label="Lebar Kertas" wire:model="paperWidth">
                    <option value="58">58 mm</option>
                    <option value="80">80 mm</option>
                </x-select>
                <x-input label="Jumlah Salinan" type="number" min="1" max="5" wire:model="copies" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Catatan Kaki Struk</label>
                <textarea wire:model="receiptFooter" rows="3" class="block w-full rounded-lg border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 sm:text-sm"></textarea>
            </div>
            <div class="flex flex-wrap gap-6">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model="showLogo" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
                    Tampilkan logo di struk
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model="autoPrint" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
                    Cetak otomatis setelah bayar
                </label>
            </div>
        </div>

        <div x-show="tab === 'kasir'" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-select label="Pembulatan Total" wire:model="rounding">
                    <option value="0">Tidak ada</option>
                    <option value="100">Ke 100</option>
                    <option value="500">Ke 500</option>
                    <option value="1000">Ke 1.000</option>
                </x-select>
                <x-input label="Batas Diskon Kasir (%)" type="number" min="0" max="100" wire:model="maxCashierDiscountPercent" />
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="allowNegativeStock" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
                Izinkan stok minus (dengan peringatan di kasir)
            </label>
        </div>

        <div x-show="tab === 'penomoran'" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 space-y-4">
            <p class="text-sm text-slate-500">Format nomor dokumen: <code class="rounded bg-slate-100 px-1.5 py-0.5">PREFIX-LOKASI-YYMMDD-0001</code></p>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($docTypeLabels as $type => $label)
                    <x-input :label="$label" wire:model="docPrefixes.{{ $type }}" maxlength="5" />
                @endforeach
            </div>
        </div>
    </form>
</div>
