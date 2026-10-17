<div class="mb-2 flex flex-wrap gap-2">
    @foreach(['today' => 'Hari ini', 'yesterday' => 'Kemarin', '7days' => '7 hari', 'this_month' => 'Bulan ini', 'last_month' => 'Bulan lalu'] as $key => $label)
        <button type="button" wire:click="setPreset('{{ $key }}')" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 hover:bg-slate-200">{{ $label }}</button>
    @endforeach
</div>
<div class="mb-4 flex flex-wrap gap-3">
    <x-input type="date" wire:model.live="dateFrom" class="w-auto" />
    <x-input type="date" wire:model.live="dateTo" class="w-auto" />
</div>
