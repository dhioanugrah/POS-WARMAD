@props(['searchable' => false])

<div {{ $attributes->class(['overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            {{ $slot }}
        </table>
    </div>
</div>
