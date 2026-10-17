@props(['label' => null, 'name' => null, 'error' => null, 'options' => [], 'placeholder' => null])

@php
    $name = $name ?? $attributes->get('wire:model') ?? $attributes->get('name');
    $errorMessage = $error ?? ($name ? ($errors->first($name) ?: null) : null);
@endphp

<div>
    @if($label)
        <label @if($attributes->get('id')) for="{{ $attributes->get('id') }}" @endif class="block text-sm font-medium text-slate-700 mb-1">{{ $label }}</label>
    @endif

    <select {{ $attributes->class([
        'block w-full rounded-lg border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-sm',
        'ring-rose-300 focus:ring-rose-500' => $errorMessage,
        'ring-slate-300 focus:ring-emerald-600' => ! $errorMessage,
    ]) }}>
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        {{ $slot }}
        @foreach($options as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>

    @if($errorMessage)
        <p class="mt-1 text-sm text-rose-600">{{ $errorMessage }}</p>
    @endif
</div>
