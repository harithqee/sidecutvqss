<div x-data="{
    rows: [],
    loaded: false,
    fromDate: null,
    toDate: null,

    get max() { return Math.max(1, ...this.rows.map(r => r.completed)); },
    get total() { return this.rows.reduce((sum, r) => sum + r.completed, 0); },

    formatLocalDate(d) {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    },

    async load() {
        let url = '/api/stats/barber-performance';
        if (this.fromDate && this.toDate) {
            url += '?from=' + this.formatLocalDate(this.fromDate) + '&to=' + this.formatLocalDate(this.toDate);
        }
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        this.rows = data.slice().sort((a, b) => b.completed - a.completed);
        this.loaded = true;
    },

    init() {
        this.load();
    },

    onDateRangeChange(detail) {
        this.fromDate = detail.from ? new Date(detail.from) : null;
        this.toDate = detail.to ? new Date(detail.to) : null;
        this.load();
    },

    share(row) {
        return this.total ? Math.round(row.completed / this.total * 100) : 0;
    }
}" @barber-range.window="onDateRangeChange($event.detail)"
   class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Barber Performance</h3>
            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                <span class="font-medium tabular-nums text-gray-800 dark:text-white/90" x-text="total"></span>
                customers served <span x-text="fromDate ? 'in the selected range' : 'today'"></span>
            </p>
        </div>
        <x-form.date-range event="barber-range" placeholder="Today" />
    </div>

    {{-- Plain bars: a handful of barbers doesn't need a charting library. --}}
    <ul class="mt-6 space-y-5">
        <template x-for="(row, index) in rows" :key="row.name">
            <li>
                <div class="mb-2 flex items-center justify-between gap-3 text-theme-sm">
                    <span class="flex min-w-0 items-center gap-2.5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-theme-xs font-semibold" :class="sc.tone(row.name)" x-text="sc.initials(row.name)"></span>
                        <span class="truncate font-medium text-gray-700 dark:text-gray-300" x-text="row.name"></span>
                        <span x-show="index === 0 && row.completed > 0" class="shrink-0 rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">Top</span>
                    </span>
                    <span class="shrink-0 tabular-nums">
                        <span class="font-semibold text-gray-900 dark:text-white" x-text="row.completed"></span>
                        <span class="ml-1 text-theme-xs text-gray-400 dark:text-gray-500" x-text="share(row) + '%'"></span>
                    </span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/5">
                    <div class="h-full rounded-full transition-[width] duration-500"
                        :class="index === 0 && row.completed > 0 ? 'bg-brand-500' : 'bg-brand-300 dark:bg-brand-400/60'"
                        :style="'width:' + (row.completed / max * 100) + '%'"></div>
                </div>
            </li>
        </template>
    </ul>

    <p x-show="loaded && rows.length === 0" class="mt-6 text-center text-theme-sm text-gray-500 dark:text-gray-400">No barbers yet.</p>
    <p x-show="loaded && rows.length > 0 && total === 0" class="mt-4 text-theme-xs text-gray-500 dark:text-gray-400">Nobody has finished a haircut in this range yet.</p>
</div>
