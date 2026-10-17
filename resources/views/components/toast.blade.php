{{--
    Global toast host. Livewire components trigger it via:
        $this->dispatch('notify', message: 'Tersimpan', type: 'success');
    Types: success (emerald), error (rose), warning (amber), info (sky).
--}}
<div
    x-data="{
        toasts: [],
        push(t) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type: t.type ?? 'success', message: t.message ?? '' });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
    }"
    x-on:notify.window="push($event.detail)"
    class="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4 sm:items-end sm:right-4 sm:left-auto"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg p-4 shadow-lg ring-1 ring-black/5"
            x-bind:class="{
                'bg-emerald-50 text-emerald-800': toast.type === 'success',
                'bg-rose-50 text-rose-800': toast.type === 'error',
                'bg-amber-50 text-amber-800': toast.type === 'warning',
                'bg-sky-50 text-sky-800': toast.type === 'info',
            }"
        >
            <p class="flex-1 text-sm font-medium" x-text="toast.message"></p>
            <button type="button" x-on:click="remove(toast.id)" class="text-current/60 hover:text-current">&times;</button>
        </div>
    </template>
</div>
