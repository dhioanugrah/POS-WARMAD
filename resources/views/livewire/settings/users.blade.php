<div>
    <x-page-header title="Pengguna" description="Kelola akun kasir, admin, dan gudang.">
        <x-slot:actions>
            <x-button wire:click="create">
                <x-icon name="plus" class="size-4" /> Tambah Pengguna
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Cari nama / email..." class="max-w-sm" />
    </div>

    @if($users->isEmpty())
        <x-empty-state title="Belum ada pengguna" />
    @else
        <x-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Peran</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($users as $user)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                        <td class="px-4 py-3"><x-badge variant="info">{{ $user->getRoleNames()->first() }}</x-badge></td>
                        <td class="px-4 py-3">
                            <button wire:click="toggleActive({{ $user->id }})">
                                <x-badge :variant="$user->is_active ? 'success' : 'neutral'">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="edit({{ $user->id }})" class="text-slate-500 hover:text-emerald-600"><x-icon name="pencil" class="size-4" /></button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
        <div class="mt-4">{{ $users->links() }}</div>
    @endif

    <x-modal :show="$showModal" maxWidth="md">
        <form wire:submit="save" class="p-6">
            <h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Ubah Pengguna' : 'Tambah Pengguna' }}</h2>
            <div class="mt-4 space-y-4">
                <x-input label="Nama" wire:model="name" />
                <div class="grid grid-cols-2 gap-4">
                    <x-input label="Email" type="email" wire:model="email" />
                    <x-input label="Username (opsional)" wire:model="username" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <x-select label="Peran" wire:model="role" placeholder="Pilih peran" :options="$roles->mapWithKeys(fn($r) => [$r => $r])" />
                    <x-select label="Lokasi Default" wire:model="defaultLocationId" placeholder="- Tidak ada -" :options="$locations" />
                </div>
                <x-input label="{{ $editingId ? 'Password Baru (opsional)' : 'Password' }}" type="password" wire:model="password" />
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
                    Aktif
                </label>
                @error('isActive') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <x-button type="button" variant="secondary" wire:click="$set('showModal', false)">Batal</x-button>
                <x-button type="submit">Simpan</x-button>
            </div>
        </form>
    </x-modal>
</div>
