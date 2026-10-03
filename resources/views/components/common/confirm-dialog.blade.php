<div x-data x-show="$store.confirm.open" x-cloak style="display: none;"
    @keydown.escape.window="$store.confirm.open && $store.confirm.answer(false)"
    class="fixed inset-0 z-999999 flex items-end justify-center p-4 sm:items-center"
    role="alertdialog" aria-modal="true" aria-labelledby="confirm-title">
    <div class="absolute inset-0 bg-gray-900/50" @click="$store.confirm.answer(false)"
        x-show="$store.confirm.open" x-transition.opacity></div>

    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-theme-xl dark:bg-gray-900 dark:ring-1 dark:ring-gray-800"
        x-show="$store.confirm.open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-init="$watch('$store.confirm.open', open => open && $nextTick(() => $refs.cancel.focus()))">
        <h2 id="confirm-title" class="text-lg font-semibold text-gray-800 dark:text-white/90" x-text="$store.confirm.title"></h2>
        <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400" x-text="$store.confirm.message"></p>

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" x-ref="cancel" @click="$store.confirm.answer(false)"
                class="h-11 rounded-lg border border-gray-300 px-4 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5"
                x-text="$store.confirm.cancelText"></button>
            <button type="button" @click="$store.confirm.answer(true)"
                class="h-11 rounded-lg px-4 text-theme-sm font-medium text-white shadow-theme-xs transition"
                :class="$store.confirm.danger ? 'bg-error-600 hover:bg-error-700' : 'bg-brand-500 hover:bg-brand-600'"
                x-text="$store.confirm.confirmText"></button>
        </div>
    </div>
</div>
