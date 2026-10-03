<div x-data="{
    logs: [],
    loaded: false,
    fromDate: null,
    toDate: null,
    status: '',
    expanded: null,
    currentPage: 1,
    lastPage: 1,
    total: 0,
    from: 0,
    to: 0,
    filters: [
        { value: '', label: 'All' },
        { value: 'sent', label: 'Sent' },
        { value: 'failed', label: 'Failed' },
        { value: 'skipped', label: 'Skipped' },
    ],

    formatLocalDate(d) {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    },

    async init() {
        await this.loadLogs();
    },

    async loadLogs(page) {
        page = page || 1;
        let url = '/api/sms-logs?page=' + page;
        if (this.fromDate && this.toDate) {
            url += '&from=' + this.formatLocalDate(this.fromDate) + '&to=' + this.formatLocalDate(this.toDate);
        }
        if (this.status) url += '&status=' + this.status;
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        const pageRes = await res.json();
        this.logs = pageRes.data.map(l => ({
            id: l.id,
            phone: l.phone,
            customerName: l.ticket ? l.ticket.customer_name : null,
            templateName: l.template ? l.template.name : '—',
            message: l.message_body || '',
            status: l.status,
            sentDay: (l.sent_at || l.created_at) ? new Date(l.sent_at || l.created_at).toLocaleDateString(undefined, { day: 'numeric', month: 'short' }) : '',
            sentTime: sc.time(l.sent_at || l.created_at),
        }));
        this.expanded = null;
        this.currentPage = pageRes.current_page;
        this.lastPage = pageRes.last_page;
        this.total = pageRes.total;
        this.from = pageRes.from || 0;
        this.to = pageRes.to || 0;
        this.loaded = true;
    },

    setStatus(value) {
        this.status = value;
        this.loadLogs(1);
    },

    goToPage(page) {
        if (page < 1 || page > this.lastPage || page === this.currentPage) return;
        this.loadLogs(page);
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
        this.loadLogs(1);
    },

    statusClass(status) {
        return {
            sent: 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-500',
            failed: 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-500',
            pending: 'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-400',
            skipped: 'bg-gray-100 text-gray-500 dark:bg-gray-500/15 dark:text-gray-400',
        }[status] || '';
    },

    statusLabel(status) {
        return { sent: 'Sent', failed: 'Failed', pending: 'Pending', skipped: 'Skipped (Inactive)' }[status] || status;
    }
}"
@sms-range.window="onDateRangeChange($event.detail)">

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <div class="inline-flex items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900" role="group" aria-label="Filter by status">
                <template x-for="filter in filters" :key="filter.value">
                    <button type="button" @click="setStatus(filter.value)" :aria-pressed="status === filter.value"
                        class="rounded-md px-3 py-1.5 text-theme-sm font-medium transition"
                        :class="status === filter.value ? 'bg-white text-gray-900 shadow-theme-xs dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                        x-text="filter.label"></button>
                </template>
            </div>
            <x-form.date-range event="sms-range" />
        </div>

        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <table class="w-full min-w-[820px] table-fixed">
                <colgroup>
                    <col class="w-[120px]"><col class="w-[190px]"><col class="w-[200px]"><col><col class="w-[150px]">
                </colgroup>
                <thead>
                    <tr class="border-b border-gray-100 text-left text-theme-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        <th class="px-5 py-3 font-medium">Sent</th>
                        <th class="px-5 py-3 font-medium">To</th>
                        <th class="px-5 py-3 font-medium">Template</th>
                        <th class="px-5 py-3 font-medium">Message</th>
                        <th class="px-5 py-3 font-medium">Result</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <template x-for="log in logs" :key="log.id">
                        <tr class="align-top text-theme-sm">
                            <td class="px-5 py-3.5 tabular-nums">
                                <span class="block text-gray-700 dark:text-gray-300" x-text="log.sentTime"></span>
                                <span class="text-theme-xs text-gray-400 dark:text-gray-500" x-text="log.sentDay"></span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="block truncate font-medium text-gray-800 dark:text-white/90" x-text="log.customerName || 'Unknown'"></span>
                                <span class="text-theme-xs tabular-nums text-gray-500 dark:text-gray-400" x-text="log.phone"></span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-600 dark:text-gray-300" x-text="log.templateName"></td>
                            <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400">
                                <template x-if="log.message">
                                    <button type="button" @click="expanded = expanded === log.id ? null : log.id" class="block w-full text-left"
                                        :aria-expanded="expanded === log.id" :title="expanded === log.id ? 'Collapse' : 'Show full message'">
                                        <span :class="expanded === log.id ? 'whitespace-normal text-gray-700 dark:text-gray-300' : 'block truncate'" x-text="log.message"></span>
                                    </button>
                                </template>
                                <span x-show="!log.message" class="text-gray-400 dark:text-gray-500">No message body</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="text-theme-xs inline-block whitespace-nowrap rounded-full px-2 py-0.5 font-medium" :class="statusClass(log.status)" x-text="statusLabel(log.status)"></p>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div x-show="loaded && logs.length === 0" class="px-5 py-14 text-center">
            <p class="text-theme-sm font-medium text-gray-700 dark:text-gray-300">No messages match</p>
            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">Try another status or a wider date range.</p>
        </div>

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
