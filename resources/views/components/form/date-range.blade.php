{{--
    Range picker that dispatches `event` on window with { from: Date|null, to: Date|null }.
    Pass days="7" to start with the last N days selected; omit it to start empty (clearable).
--}}
@props(['event', 'days' => null, 'placeholder' => 'All dates'])

<div x-data="{
        hasValue: {{ $days ? 'true' : 'false' }},
        picker: null,
        init() {
            const days = {{ $days ? (int) $days : 'null' }};
            // Midnight, so today's date isn't rejected by maxDate for being later in the day.
            const today = new Date(new Date().setHours(0, 0, 0, 0));
            this.picker = flatpickr(this.$refs.input, {
                mode: 'range',
                static: true,
                monthSelectorType: 'static',
                dateFormat: 'M j',
                maxDate: 'today',
                defaultDate: days ? [new Date(today.getTime() - (days - 1) * 86400000), today] : null,
                prevArrow: '<svg class=\'stroke-current\' width=\'24\' height=\'24\' viewBox=\'0 0 24 24\' fill=\'none\'><path d=\'M15.25 6L9 12.25L15.25 18.5\' stroke-width=\'1.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/></svg>',
                nextArrow: '<svg class=\'stroke-current\' width=\'24\' height=\'24\' viewBox=\'0 0 24 24\' fill=\'none\'><path d=\'M8.75 19L15 12.75L8.75 6.5\' stroke-width=\'1.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/></svg>',
                onReady: (selected, dateStr, instance) => {
                    instance.element.value = dateStr.replace(' to ', ' – ');
                    instance.calendarContainer?.classList.add('flatpickr-right');
                },
                onChange: (selected, dateStr, instance) => {
                    instance.element.value = dateStr.replace(' to ', ' – ');
                    if (selected.length === 2) {
                        this.hasValue = true;
                        window.dispatchEvent(new CustomEvent(@js($event), { detail: { from: selected[0], to: selected[1] } }));
                    }
                },
            });
        },
        clear() {
            this.picker.clear();
            this.hasValue = false;
            window.dispatchEvent(new CustomEvent(@js($event), { detail: { from: null, to: null } }));
        }
    }"
    {{ $attributes->merge(['class' => 'relative']) }}>
    <svg class="pointer-events-none absolute left-3 top-1/2 z-1 -translate-y-1/2 text-gray-500 dark:text-gray-400" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="3" y="4.5" width="18" height="16.5" rx="2"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/>
    </svg>
    <input x-ref="input" readonly placeholder="{{ $placeholder }}" aria-label="Date range"
        class="h-10 w-[188px] cursor-pointer rounded-lg border border-gray-200 bg-white pl-9 pr-8 text-theme-sm font-medium text-gray-700 shadow-theme-xs outline-none placeholder:font-normal placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
    <button type="button" x-show="hasValue" x-cloak @click="clear()" aria-label="Clear date range"
        class="absolute right-2 top-1/2 z-1 -translate-y-1/2 rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5 dark:hover:text-gray-200">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
    </button>
</div>
