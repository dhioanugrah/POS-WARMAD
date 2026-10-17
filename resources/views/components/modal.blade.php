@props(['show' => false, 'maxWidth' => '2xl', 'close' => null, 'dismissable' => true])

@php
$maxWidthClass = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

{{--
    Visibility is driven server-side by the `$show` prop (a Livewire boolean
    property), not client Alpine state — this way toggling `$showModal` in a
    Livewire method reliably opens/closes the modal on every re-render.
--}}
@if($show)
    <div
        x-data
        @if($dismissable)
            x-on:keydown.escape.window="$wire.{{ $close ?? 'showModal' }} = false"
        @endif
        class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    >
        <div
            class="fixed inset-0 bg-slate-900/50 transition-opacity"
            @if($dismissable)
                wire:click="$set('{{ $close ?? 'showModal' }}', false)"
            @endif
        ></div>

        <div class="relative mb-6 bg-white rounded-xl overflow-hidden shadow-xl transform transition-all sm:w-full {{ $maxWidthClass }} sm:mx-auto">
            {{ $slot }}
        </div>
    </div>
@endif
