@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Kas Masuk / Keluar" description="Catatan biaya operasional dan pemasukan lain.">
        <x-slot:actions>
            <a href="{{ route('cash.categories') }}" wire:navigate><x-button variant="secondary">Kategori</x-button></a>
            <x-button wire:click="create"><x-icon name="plus" class="size-4" /> Transaksi Baru</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
        <x-input type="date" wire:model.live="dateFrom" />
        <x-input type="date" wire:model.live="dateTo" />
        <x-select wire:model.live="typeFilter" placeholder="Semua Tipe" :options="['in' => 'Masuk', 'out' => 'Keluar']" />
        <x-select wire:model.live="categoryFilter" placeholder="Semua Kategori" :options="$categories" />
    </div>

    <div class="mb-4 grid grid-cols-2 gap-3 sm:w-96">
        <x-stat-card label="Total Masuk" :value="Money::format($totalIn)" variant="success" />
        <x-stat-card label="Total Keluar" :value="Money::format($totalOut)" variant="danger" />
    </div>

    @if($transactions->isEmpty())
        <x-empty-state title="Belum ada transaksi kas" />
    @else
        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">No.</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kategori</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Keterangan</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Jumlah</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($transactions as $trx)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $trx->number }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $trx->occurred_at->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            {{ $trx->category->name }}
                            @if($trx->shift_id)
                                <x-badge variant="info" class="ml-1">Laci Shift #{{ $trx->shift_id }}</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $trx->description }}</td>
                        <td class="px-4 py-3 text-right {{ $trx->type->value === 'in' ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $trx->type->value === 'in' ? '+' : '-' }}{{ Money::format($trx->amount) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($trx->attachment_path)
                                <a href="{{ Storage::url($trx->attachment_path) }}" target="_blank" class="text-emerald-600 hover:underline text-xs">Lihat Bukti</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
        <div class="mt-4">{{ $transactions->links() }}</div>
    @endif

    <x-modal :show="$showModal" maxWidth="md">
        <form wire:submit="save" class="p-6">
            <h2 class="text-lg font-semibold text-slate-900">Transaksi Kas Baru</h2>
            <div class="mt-4 space-y-4">
                <div class="flex gap-2">
                    <button type="button" wire:click="$set('type', 'out')" class="flex-1 rounded-lg py-2 text-sm font-medium {{ $type === 'out' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-600' }}">Keluar</button>
                    <button type="button" wire:click="$set('type', 'in')" class="flex-1 rounded-lg py-2 text-sm font-medium {{ $type === 'in' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600' }}">Masuk</button>
                </div>
                <x-select label="Kategori" wire:model="cashCategoryId" placeholder="- Pilih Kategori -" :options="$categories" />
                <div class="grid grid-cols-2 gap-4">
                    <x-select label="Metode" wire:model="method" :options="['cash' => 'Tunai', 'bank' => 'Bank']" />
                    <x-input label="Tanggal" type="date" wire:model="occurredAt" />
                </div>
                <x-money-input label="Jumlah" wire:model="amount" />
                <x-input label="Keterangan" wire:model="description" />
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Lampiran Foto Bukti (opsional)</label>
                    <input type="file" wire:model="attachment" accept="image/*" class="text-sm text-slate-600">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <x-button type="button" variant="secondary" wire:click="$set('showModal', false)">Batal</x-button>
                <x-button type="submit">Simpan</x-button>
            </div>
        </form>
    </x-modal>
</div>
