<div class="pointer-events-none fixed inset-x-4 bottom-4 z-999999 flex flex-col items-end gap-2 sm:inset-x-auto sm:right-6 sm:bottom-6"
    x-data aria-live="polite" role="status">
    <template x-for="item in $store.toast.items" :key="item.id">
        <div x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="pointer-events-auto flex w-full items-start gap-3 rounded-xl border bg-white py-3 pl-3 pr-2 shadow-theme-lg dark:bg-gray-800 sm:w-[360px]"
            :class="{
                'border-gray-200 dark:border-gray-700': item.type === 'success' || item.type === 'info',
                'border-error-200 dark:border-error-500/40': item.type === 'error',
                'border-warning-200 dark:border-warning-500/40': item.type === 'warning',
            }">
            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                :class="{
                    'bg-success-500 text-white': item.type === 'success',
                    'bg-error-500 text-white': item.type === 'error',
                    'bg-warning-500 text-white': item.type === 'warning',
                    'bg-brand-500 text-white': item.type === 'info',
                }">
                <svg x-show="item.type === 'success'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg>
                <svg x-show="item.type === 'error'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                <svg x-show="item.type === 'warning' || item.type === 'info'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M12 7v6M12 17h.01"/></svg>
            </span>
            <div class="min-w-0 flex-1">
                <p x-show="item.title" class="text-theme-sm font-semibold text-gray-800 dark:text-white/90" x-text="item.title"></p>
                <p class="text-theme-sm text-gray-600 dark:text-gray-300" x-text="item.message"></p>
            </div>
            <button type="button" @click="$store.toast.remove(item.id)"
                class="rounded-md p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5 dark:hover:text-gray-200"
                aria-label="Dismiss">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
    </template>
</div>
