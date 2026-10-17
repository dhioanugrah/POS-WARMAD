@props(['title', 'description' => null])

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900">{{ $title }}</h1>
        @if($description)
            <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
        @endif
    </div>
    @if(isset($actions))
        <div class="flex flex-shrink-0 items-center gap-2">{{ $actions }}</div>
    @endif
</div>
