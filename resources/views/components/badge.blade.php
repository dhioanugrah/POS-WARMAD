@props(['variant' => 'neutral'])

@php
    $variants = [
        'neutral' => 'bg-slate-100 text-slate-700',
        'success' => 'bg-emerald-100 text-emerald-700',
        'warning' => 'bg-amber-100 text-amber-800',
        'danger' => 'bg-rose-100 text-rose-700',
        'info' => 'bg-sky-100 text-sky-700',
    ][$variant] ?? '';
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', $variants]) }}>
    {{ $slot }}
</span>
