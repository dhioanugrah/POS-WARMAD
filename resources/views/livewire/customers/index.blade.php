@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Pelanggan" description="Kelola daftar pelanggan dan batas kredit.">
        <x-slot:actions>
            <x-button wire:click="create"><x-icon name="plus" class="size-4" /> Tambah Pelanggan</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Cari nama / kode / telepon..." class="max-w-sm" />
    </div>

    @if($customers->isEmpty())
        <x-empty-state title="Belum ada pelanggan" description="Penjualan umum tidak perlu memilih pelanggan." />
    @else
        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kode</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Telepon</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Batas Kredit</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($customers as $customer)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $customer->code }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $customer->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $customer->phone ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">{{ Money::format($customer->credit_limit) }}</td>
                        <td class="px-4 py-3"><x-badge :variant="$customer->is_active ? 'success' : 'neutral'">{{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="edit({{ $customer->id }})" class="text-slate-500 hover:text-emerald-600"><x-icon name="pencil" class="size-4" /></button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
        <div class="mt-4">{{ $customers->links() }}</div>
    @endif

    <x-modal :show="$showModal" maxWidth="md">
        <form wire:submit="save" class="p-6">
            <h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Ubah Pelanggan' : 'Tambah Pelanggan' }}</h2>
            <div class="mt-4 space-y-4">
                <x-input label="Nama" wire:model="name" />
                <div class="grid grid-cols-2 gap-4">
                    <x-input label="Telepon" wire:model="phone" />
                    <x-input label="Email" wire:model="email" />
                </div>
                <x-input label="Alamat" wire:model="address" />
                <x-money-input label="Batas Kredit" wire:model="creditLimit" />
                <x-input label="Catatan" wire:model="notes" />
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
                    Aktif
                </label>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <x-button type="button" variant="secondary" wire:click="$set('showModal', false)">Batal</x-button>
                <x-button type="submit">Simpan</x-button>
            </div>
        </form>
    </x-modal>
</div>
