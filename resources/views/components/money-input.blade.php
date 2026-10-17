@props(['label' => null, 'name' => null, 'error' => null])

@php
    $wireModel = $attributes->wire('model')->value();
    $name = $name ?? $attributes->get('name');
    $errorMessage = $error ?? ($name ? ($errors->first($name) ?: null) : null);
@endphp

{{--
    Displays a thousands-formatted string to the user while keeping the
    underlying Livewire-bound value a plain integer (Tech Spec §T-004 AC).
--}}
<div
    x-data="{
        raw: @entangle($wireModel),
        display: '',
        format(v) {
            let n = parseInt(String(v).replace(/[^0-9-]/g, '')) || 0;
            return n.toLocaleString('id-ID');
        },
        onInput(e) {
            let n = parseInt(e.target.value.replace(/[^0-9-]/g, '')) || 0;
            this.raw = n;
            this.display = this.format(n);
        },
    }"
    x-init="display = format(raw); $watch('raw', v => display = format(v))"
>
    @if($label)
        <label class="block text-sm font-medium text-slate-700 mb-1">{{ $label }}</label>
    @endif

    <div class="relative">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-sm">Rp</span>
        <input
            type="text"
            inputmode="numeric"
            x-model="display"
            x-on:input="onInput($event)"
            {{ $attributes->except(['wire:model'])->class([
                'block w-full rounded-lg border-0 py-2 pl-9 pr-3 text-right font-medium text-slate-900 shadow-sm ring-1 ring-inset placeholder:text-slate-400 focus:ring-2 focus:ring-inset sm:text-sm',
                'ring-rose-300 focus:ring-rose-500' => $errorMessage,
                'ring-slate-300 focus:ring-emerald-600' => ! $errorMessage,
            ]) }}
        />
    </div>

    @if($errorMessage)
        <p class="mt-1 text-sm text-rose-600">{{ $errorMessage }}</p>
    @endif
</div>
