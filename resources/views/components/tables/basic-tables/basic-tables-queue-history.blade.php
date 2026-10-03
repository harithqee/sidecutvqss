<div x-data="{
    history: [],
    loaded: false,
    fromDate: null,
    toDate: null,
    currentPage: 1,
    lastPage: 1,
    total: 0,
    from: 0,
    to: 0,

    formatLocalDate(d) {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    },

    async init() {
        await this.loadHistory();
    },

    async loadHistory(page) {
        page = page || 1;
        let url = '/api/queue/history?page=' + page;
        if (this.fromDate && this.toDate) {
            url += '&from=' + this.formatLocalDate(this.fromDate) + '&to=' + this.formatLocalDate(this.toDate);
        }
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        const pageRes = await res.json();
        this.history = pageRes.data.map(t => ({
            id: t.id,
            queueNumber: t.queue_number,
            name: t.customer_name,
            phone: t.customer_phone,
            server: t.barber ? t.barber.name : null,
            joinedAt: sc.time(t.joined_at),
            date: t.session ? new Date(t.session.session_date + 'T00:00:00').toLocaleDateString(undefined, { day: 'numeric', month: 'short' }) : '',
            waitMinutes: t.waiting_time ? parseInt(t.waiting_time) : null,
            serviceMinutes: t.served_at && t.finished_at ? Math.max(0, Math.round((new Date(t.finished_at) - new Date(t.served_at)) / 60000)) : null,
            finishedAt: sc.time(t.finished_at),
            status: t.status
        }));
        this.currentPage = pageRes.current_page;
        this.lastPage = pageRes.last_page;
        this.total = pageRes.total;
        this.from = pageRes.from || 0;
        this.to = pageRes.to || 0;
        this.loaded = true;
    },

    goToPage(page) {
        if (page < 1 || page > this.lastPage || page === this.currentPage) return;
        this.loadHistory(page);
    },

    get pageNumbers() {
        const pages = [];
        const start = Math.max(1, Math.min(this.currentPage - 2, this.lastPage - 4));
        const end = Math.min(this.lastPage, start + 4);
        for (let i = start; i <= end; i++) pages.push(i);
        return pages;
    },

    onDateRangeChange(detail) {
        this.fromDate = detail.from ? new Date(detail.from) : null;
        this.toDate = detail.to ? new Date(detail.to) : null;
        this.loadHistory(1);
    },

    waitClass(minutes) {
        if (minutes === null) return 'text-gray-400 dark:text-gray-500';
        if (minutes >= 30) return 'text-error-600 dark:text-error-400 font-medium';
        if (minutes >= 20) return 'text-warning-600 dark:text-warning-400 font-medium';
        return 'text-gray-700 dark:text-gray-300';
    }
}"
@history-range.window="onDateRangeChange($event.detail)">

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">
                Finished and cancelled tickets<span x-show="!fromDate">, newest first</span><span x-show="fromDate">, filtered by date</span>.
            </p>
            <x-form.date-range event="history-range" />
        </div>

        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <table class="w-full min-w-[820px]">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-theme-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        <th class="px-5 py-3 font-medium">Ticket</th>
                        <th class="px-5 py-3 font-medium">Customer</th>
                        <th class="px-5 py-3 font-medium">Barber</th>
                        <th class="px-5 py-3 font-medium">Joined</th>
                        <th class="px-5 py-3 text-right font-medium">Waited</th>
                        <th class="px-5 py-3 text-right font-medium">In chair</th>
                        <th class="px-5 py-3 font-medium">Outcome</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <template x-for="entry in history" :key="entry.id">
                        <tr class="text-theme-sm">
                            <td class="px-5 py-3.5 font-mono font-semibold tabular-nums text-gray-900 dark:text-white">
                                <span class="text-gray-300 dark:text-gray-600">#</span><span x-text="String(entry.queueNumber ?? '').padStart(3, '0')"></span>
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="font-medium text-gray-800 dark:text-white/90" x-text="entry.name"></p>
                                <p class="text-theme-xs tabular-nums text-gray-500 dark:text-gray-400" x-text="entry.phone"></p>
                            </td>
                            <td class="px-5 py-3.5 text-gray-600 dark:text-gray-300" x-text="entry.server || '—'"></td>
                            <td class="px-5 py-3.5 tabular-nums">
                                <span class="text-gray-700 dark:text-gray-300" x-text="entry.joinedAt"></span>
                                <span class="ml-1 text-theme-xs text-gray-400 dark:text-gray-500" x-text="entry.date"></span>
                            </td>
                            <td class="px-5 py-3.5 text-right tabular-nums" :class="waitClass(entry.waitMinutes)" x-text="entry.waitMinutes === null ? '—' : entry.waitMinutes + ' min'"></td>
                            <td class="px-5 py-3.5 text-right tabular-nums text-gray-700 dark:text-gray-300" x-text="entry.serviceMinutes === null ? '—' : entry.serviceMinutes + ' min'"></td>
                            <td class="px-5 py-3.5">
                                <p class="text-theme-xs inline-block whitespace-nowrap rounded-full px-2 py-0.5 font-medium" :class="entry.status === 'completed' ? 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-500' : 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-500'"
                                    x-text="entry.status === 'completed' ? 'Served · ' + entry.finishedAt : 'Cancelled'"></p>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div x-show="loaded && history.length === 0" class="px-5 py-14 text-center">
            <p class="text-theme-sm font-medium text-gray-700 dark:text-gray-300">Nothing here for those dates</p>
            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">Try a wider date range.</p>
        </div>

        <!-- Pagination -->
        <div x-show="total > 0" class="flex flex-col items-center justify-between gap-3 border-t border-gray-100 px-5 py-3.5 dark:border-gray-800 sm:flex-row">
            <p class="text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                <span x-text="from"></span>–<span x-text="to"></span> of <span x-text="total"></span>
            </p>
            <div class="flex items-center gap-1" x-show="lastPage > 1">
                <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" type="button" aria-label="Previous page"
                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <template x-for="p in pageNumbers" :key="p">
                    <button @click="goToPage(p)" type="button"
                        class="h-8 min-w-8 rounded-lg px-2 text-theme-xs font-medium tabular-nums transition"
                        :class="p === currentPage ? 'bg-brand-500 text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5'"
                        :aria-current="p === currentPage ? 'page' : null"
                        x-text="p"></button>
                </template>
                <button @click="goToPage(currentPage + 1)" :disabled="currentPage === lastPage" type="button" aria-label="Next page"
                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>
