@php
    $initialToasts = collect([
        'ok' => 'success',
        'error' => 'error',
        'info' => 'info',
    ])->filter(fn (string $type, string $key) => session()->has($key))
        ->map(fn (string $type, string $key) => ['type' => $type, 'message' => session($key)])
        ->values();
@endphp

<div
    x-data="toastNotifications(@js($initialToasts))"
    x-on:app-toast.window="add($event.detail)"
    class="pointer-events-none fixed bottom-4 right-4 z-[100] flex w-[calc(100vw-2rem)] max-w-sm flex-col gap-3 sm:bottom-6 sm:right-6"
    aria-live="polite"
    aria-atomic="true"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-4 opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-4 opacity-0"
            class="pointer-events-auto flex items-start gap-3 rounded-xl border p-4 shadow-lg"
            :class="{
                'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200': toast.type === 'success',
                'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200': toast.type === 'error',
                'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200': toast.type === 'info',
            }"
            role="status"
        >
            <x-icon name="check" x-show="toast.type === 'success'" class="mt-0.5 h-5 w-5 shrink-0" />
            <x-icon name="alert" x-show="toast.type === 'error'" class="mt-0.5 h-5 w-5 shrink-0" />
            <x-icon name="alert" x-show="toast.type === 'info'" class="mt-0.5 h-5 w-5 shrink-0" />
            <p class="flex-1 text-sm font-medium" x-text="toast.message"></p>
            <button type="button" x-on:click="remove(toast.id)" class="rounded p-0.5 opacity-70 transition hover:opacity-100" aria-label="Cerrar notificación">
                <x-icon name="close" class="h-4 w-4" />
            </button>
        </div>
    </template>
</div>
