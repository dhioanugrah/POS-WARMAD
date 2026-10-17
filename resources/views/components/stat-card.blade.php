@props(['label', 'value', 'hint' => null, 'variant' => 'neutral', 'icon' => null])

@php
    $accents = [
        'neutral' => 'text-slate-500',
        'success' => 'text-emerald-600',
        'danger' => 'text-rose-600',
        'warning' => 'text-amber-600',
        'info' => 'text-sky-600',
    ][$variant] ?? 'text-slate-500';
@endphp

<div {{ $attributes->class(['rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5']) }}>
    <div class="flex items-center justify-between">
        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
        @if($icon)
            <span class="{{ $accents }}">{{ $icon }}</span>
        @endif
    </div>
    <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $value }}</p>
    @if($hint)
        <p class="mt-1 text-sm {{ $accents }}">{{ $hint }}</p>
    @endif
</div>
