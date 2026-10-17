@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'tag' => 'button',
])

@php
    $variants = [
        'primary' => 'bg-emerald-600 text-white hover:bg-emerald-700 focus-visible:outline-emerald-600 disabled:bg-emerald-300',
        'secondary' => 'bg-white text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus-visible:outline-slate-600 disabled:text-slate-400',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700 focus-visible:outline-rose-600 disabled:bg-rose-300',
        'ghost' => 'text-slate-600 hover:bg-slate-100 focus-visible:outline-slate-400',
        'warning' => 'bg-amber-500 text-white hover:bg-amber-600 focus-visible:outline-amber-500 disabled:bg-amber-300',
    ][$variant] ?? '';

    $sizes = [
        'sm' => 'px-2.5 py-1.5 text-sm gap-1.5',
        'md' => 'px-3.5 py-2 text-sm gap-2',
        'lg' => 'px-4 py-2.5 text-base gap-2',
        'touch' => 'px-5 py-3.5 text-base gap-2 min-h-[44px]',
    ][$size] ?? '';
@endphp

<{{ $tag }}
    @if($tag === 'button') type="{{ $type }}" @endif
    {{ $attributes->class([
        'inline-flex items-center justify-center rounded-lg font-semibold shadow-sm transition disabled:cursor-not-allowed disabled:shadow-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2',
        $variants,
        $sizes,
    ]) }}
>{{ $slot }}</{{ $tag }}>
