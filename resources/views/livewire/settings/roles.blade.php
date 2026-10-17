<div>
    <x-page-header title="Hak Akses Peran" description="Owner selalu memiliki semua akses dan tidak perlu diatur.">
        <x-slot:actions>
            <x-button wire:click="save">Simpan Perubahan</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Izin</th>
                @foreach($roles as $role)
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $role }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($permissions as $permission)
                <tr>
                    <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $permission }}</td>
                    @foreach($roles as $role)
                        <td class="px-4 py-3 text-center">
                            <input
                                type="checkbox"
                                wire:click="toggle('{{ $role }}', '{{ $permission }}')"
                                @checked($matrix[$role][$permission] ?? false)
                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600"
                            >
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </x-table>
</div>
