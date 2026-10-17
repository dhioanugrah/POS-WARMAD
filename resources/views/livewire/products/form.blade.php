<div>
    <x-page-header :title="$productId ? 'Ubah Barang' : 'Tambah Barang'">
        <x-slot:actions>
            <a href="{{ route('products.index') }}" wire:navigate>
                <x-button variant="secondary">Kembali</x-button>
            </a>
        </x-slot:actions>
    </x-page-header>

    @php $countsOpeningStock = $this->countsOpeningStock(); @endphp

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
            <h2 class="mb-4 text-sm font-semibold text-slate-900">Data Utama</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input label="Kode (kosongkan untuk otomatis)" wire:model="code" />
                <x-input label="Nama Barang" wire:model="name" />
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Kategori</label>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <x-select wire:model="categoryId" placeholder="- Pilih Kategori -" :options="$categories" />
                        </div>
                        <button type="button" wire:click="openQuickAddCategory" title="Tambah kategori baru" class="flex h-9 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 px-3 text-slate-600 hover:bg-slate-200">
                            <x-icon name="plus" class="size-4" />
                        </button>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Merek (opsional)</label>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <x-select wire:model="brandId" placeholder="- Pilih Merek -" :options="$brands" />
                        </div>
                        <button type="button" wire:click="openQuickAddBrand" title="Tambah merek baru" class="flex h-9 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 px-3 text-slate-600 hover:bg-slate-200">
                            <x-icon name="plus" class="size-4" />
                        </button>
                    </div>
                </div>
                <x-select label="Tipe" wire:model="type" :options="['stock' => 'Barang Stok', 'service' => 'Jasa']" />
                <x-input label="Stok Minimum" type="number" step="0.001" wire:model="minStock" :disabled="$type === 'service'" />
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Catatan</label>
                <textarea wire:model="notes" rows="2" class="block w-full rounded-lg border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 sm:text-sm"></textarea>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Foto</label>
                <div class="flex items-center gap-4">
                    @if($newImage)
                        <img src="{{ $newImage->temporaryUrl() }}" class="size-16 rounded-lg object-cover ring-1 ring-slate-200">
                    @elseif($imagePath)
                        <img src="{{ Storage::url($imagePath) }}" class="size-16 rounded-lg object-cover ring-1 ring-slate-200">
                    @else
                        <div class="flex size-16 items-center justify-center rounded-lg bg-slate-100 text-slate-400"><x-icon name="cube" class="size-6" /></div>
                    @endif
                    <input type="file" wire:model="newImage" accept="image/*" class="text-sm text-slate-600">
                </div>
            </div>
            <label class="mt-4 flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
                Aktif (tampil di kasir)
            </label>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Satuan & Harga</h2>
                <x-button type="button" variant="secondary" size="sm" wire:click="addUnit"><x-icon name="plus" class="size-4" /> Tambah Satuan</x-button>
            </div>

            @error('units') <p class="mb-3 text-sm text-rose-600">{{ $message }}</p> @enderror

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="py-2 pr-3">Nama Satuan</th>
                            <th class="py-2 pr-3">Isi (konversi ke satuan dasar)</th>
                            <th class="py-2 pr-3">Barcode</th>
                            <th class="py-2 pr-3">Harga Jual</th>
                            @if($countsOpeningStock)
                                <th class="py-2 pr-3">Stok Awal</th>
                            @endif
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($units as $index => $unit)
                            <tr wire:key="unit-{{ $index }}">
                                <td class="py-2 pr-3 w-32">
                                    <x-input wire:model="units.{{ $index }}.unit_name" placeholder="Pcs" />
                                </td>
                                <td class="py-2 pr-3 w-40">
                                    @if($index === 0)
                                        <input type="text" value="1 (dasar)" disabled class="block w-full rounded-lg border-0 py-2 px-3 text-slate-400 bg-slate-50 ring-1 ring-inset ring-slate-200 sm:text-sm">
                                    @else
                                        <x-input type="number" step="0.001" wire:model="units.{{ $index }}.conversion_factor" placeholder="40" />
                                    @endif
                                </td>
                                <td class="py-2 pr-3 w-72">
                                    <div class="flex gap-2">
                                        <input
                                            type="text"
                                            wire:model="units.{{ $index }}.barcode_input"
                                            wire:keydown.enter.prevent="addBarcode({{ $index }})"
                                            placeholder="Scan / ketik barcode..."
                                            class="block w-full min-w-0 flex-1 rounded-lg border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600 sm:text-sm"
                                        >
                                        <button type="button" wire:click="addBarcode({{ $index }})" title="Tambah barcode" class="flex flex-shrink-0 items-center gap-1 rounded-lg bg-slate-100 px-3 text-sm text-slate-600 hover:bg-slate-200">
                                            <x-icon name="plus" class="size-4" /> Tambah
                                        </button>
                                    </div>
                                    @if(!empty($unit['barcodes']))
                                        <div class="mt-1.5 flex flex-wrap gap-1">
                                            @foreach($unit['barcodes'] as $bIndex => $barcode)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 py-0.5 pl-2 pr-1 font-mono text-xs text-slate-600">
                                                    {{ $barcode }}
                                                    <button type="button" wire:click="removeBarcode({{ $index }}, {{ $bIndex }})" class="text-slate-400 hover:text-rose-600">
                                                        <x-icon name="x-mark" class="size-3" />
                                                    </button>
                                                </span>
                                            @endforeach
                                        </div>
                                        <p class="mt-1 text-[11px] text-slate-400">{{ count($unit['barcodes']) }} barcode untuk satuan ini &mdash; scan / ketik lagi untuk tambah.</p>
                                    @endif
                                </td>
                                <td class="py-2 pr-3 w-40">
                                    <x-input type="number" step="1" wire:model="units.{{ $index }}.price_sell" placeholder="0" />
                                </td>
                                @if($countsOpeningStock)
                                    <td class="py-2 pr-3 w-28">
                                        <x-input type="number" step="0.001" min="0" wire:model="units.{{ $index }}.opening_qty" placeholder="0" />
                                    </td>
                                @endif
                                <td class="py-2">
                                    @if($index > 0)
                                        <button type="button" wire:click="removeUnit({{ $index }})" class="text-slate-400 hover:text-rose-600"><x-icon name="trash" class="size-4" /></button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-2 text-xs text-slate-400">Baris pertama adalah satuan dasar (konversi 1) dan tidak bisa dihapus. Satu satuan boleh punya banyak barcode (misalnya barang dengan barcode berbeda-beda per pcs) — cukup isi harga sekali, lalu scan tiap barcode (atau ketik manual dan klik Tambah) untuk menambahkannya ke satuan yang sama.</p>

            @if($countsOpeningStock)
                <div class="mt-4 rounded-lg bg-emerald-50 p-4 ring-1 ring-emerald-100">
                    <p class="text-sm font-medium text-emerald-900">Stok awal</p>
                    <p class="mt-0.5 text-xs text-emerald-800">Setiap scan barcode dihitung 1 barang: scan 24 botol = stok awal 24 Pcs, scan barcode dus = 1 Dus. Jumlahnya bisa juga diketik langsung di kolom Stok Awal. Saat disimpan, stok dicatat sebagai Saldo Awal.</p>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <x-input label="Harga beli per {{ $units[0]['unit_name'] ?: 'satuan dasar' }} (HPP)" type="number" step="0.01" min="0" wire:model="openingCost" placeholder="0" />
                        <x-select label="Lokasi stok awal" wire:model="openingLocationId" :options="$locations" />
                    </div>
                </div>
            @endif
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('products.index') }}" wire:navigate><x-button type="button" variant="secondary">Batal</x-button></a>
            <x-button type="submit">Simpan</x-button>
        </div>
    </form>

    <x-modal :show="$showQuickAddCategory" close="showQuickAddCategory" maxWidth="sm">
        <form wire:submit="quickAddCategory" class="p-6">
            <h2 class="text-lg font-semibold text-slate-900">Tambah Kategori Baru</h2>
            <div class="mt-4">
                <x-input label="Nama Kategori" wire:model="quickAddCategoryName" autofocus />
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <x-button type="button" variant="secondary" wire:click="$set('showQuickAddCategory', false)">Batal</x-button>
                <x-button type="submit">Simpan & Pilih</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal :show="$showQuickAddBrand" close="showQuickAddBrand" maxWidth="sm">
        <form wire:submit="quickAddBrand" class="p-6">
            <h2 class="text-lg font-semibold text-slate-900">Tambah Merek Baru</h2>
            <div class="mt-4">
                <x-input label="Nama Merek" wire:model="quickAddBrandName" autofocus />
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <x-button type="button" variant="secondary" wire:click="$set('showQuickAddBrand', false)">Batal</x-button>
                <x-button type="submit">Simpan & Pilih</x-button>
            </div>
        </form>
    </x-modal>
</div>
