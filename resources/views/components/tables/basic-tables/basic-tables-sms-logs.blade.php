<div x-data="{
    logs: [],
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
        await this.loadLogs();
    },

    async loadLogs(page) {
        page = page || 1;
        let url = '/api/sms-logs?page=' + page;
        if (this.fromDate && this.toDate) {
            url += '&from=' + this.formatLocalDate(this.fromDate) + '&to=' + this.formatLocalDate(this.toDate);
        }
        const res = await fetch(url);
        const page_res = await res.json();
        this.logs = page_res.data.map(function(l) {
            return {
                id: l.id,
                phone: l.phone,
                customerName: l.ticket ? l.ticket.customer_name : '—',
                templateName: l.template ? l.template.name : '—',
                message: l.message_body || '—',
                status: l.status,
                sentAt: l.sent_at ? new Date(l.sent_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—',
            };
        });
        this.currentPage = page_res.current_page;
        this.lastPage = page_res.last_page;
        this.total = page_res.total;
        this.from = page_res.from || 0;
        this.to = page_res.to || 0;
    },

    goToPage(page) {
        if (page < 1 || page > this.lastPage || page === this.currentPage) return;
        this.loadLogs(page);
    },

    get pageNumbers() {
        const pages = [];
        const start = Math.max(1, this.currentPage - 2);
        const end = Math.min(this.lastPage, start + 4);
        for (let i = start; i <= end; i++) pages.push(i);
        return pages;
    },

    async onDateRangeChange(fromStr, toStr) {
        this.fromDate = new Date(fromStr);
        this.toDate = new Date(toStr);
        await this.loadLogs(1);
    },

    clearSearch() {
        this.fromDate = null;
        this.toDate = null;
        this.loadLogs(1);
    },

    getStatusClass(status) {
    const classes = {
        sent: 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-500',
        failed: 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-500',
        pending: 'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-400',
        skipped: 'bg-gray-100 text-gray-500 dark:bg-gray-500/15 dark:text-gray-400',
    };
    return classes[status] || '';
},

statusLabel(status) {
    const labels = { sent: 'Sent', failed: 'Failed', pending: 'Pending', skipped: 'Skipped (Inactive)' };
    return labels[status] || status;
}
}"
@date-range-changed.window="onDateRangeChange($event.detail.from, $event.detail.to)">

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800 sm:px-6">
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg">SMS Logs</h3>
                <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400 sm:text-theme-sm">Record of every SMS attempt sent to customers.</p>
            </div>

            <div class="flex items-center gap-2">
                <div x-data="{
                    init() {
                        flatpickr(this.$refs.datepicker, {
                            mode: 'range',
                            static: true,
                            monthSelectorType: 'static',
                            dateFormat: 'M j',
                            prevArrow: '<svg class=\'stroke-current\' width=\'24\' height=\'24\' viewBox=\'0 0 24 24\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'><path d=\'M15.25 6L9 12.25L15.25 18.5\' stroke=\'\' stroke-width=\'1.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/></svg>',
                            nextArrow: '<svg class=\'stroke-current\' width=\'24\' height=\'24\' viewBox=\'0 0 24 24\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'><path d=\'M8.75 19L15 12.75L8.75 6.5\' stroke=\'\' stroke-width=\'1.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/></svg>',
                            onReady: (selectedDates, dateStr, instance) => {
                                const customClass = instance.element.getAttribute('data-class');
                                if (instance.calendarContainer) {
                                    instance.calendarContainer.classList.add(customClass);
                                }
                            },
                            onChange: (selectedDates, dateStr, instance) => {
                                instance.element.value = dateStr.replace('to', '-');
                                if (selectedDates.length === 2) {
                                    this.$dispatch('date-range-changed', { from: selectedDates[0], to: selectedDates[1] });
                                }
                            },
                        })
                    }
                }" class="relative max-w-40">
                    <input x-ref="datepicker" class="h-10 w-full max-w-11 rounded-lg border border-gray-200 bg-white py-2.5 pl-[34px] pr-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs focus:outline-hidden focus:ring-0 focus-visible:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 xl:max-w-fit xl:pl-11" placeholder="Select dates" data-class="flatpickr-right" readonly="readonly" />
                    <div class="absolute inset-0 right-auto flex items-center pointer-events-none left-4">
                        <svg class="fill-gray-700 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M6.66683 1.54199C7.08104 1.54199 7.41683 1.87778 7.41683 2.29199V3.00033H12.5835V2.29199C12.5835 1.87778 12.9193 1.54199 13.3335 1.54199C13.7477 1.54199 14.0835 1.87778 14.0835 2.29199V3.00033L15.4168 3.00033C16.5214 3.00033 17.4168 3.89576 17.4168 5.00033V7.50033V15.8337C17.4168 16.9382 16.5214 17.8337 15.4168 17.8337H4.5835C3.47893 17.8337 2.5835 16.9382 2.5835 15.8337V7.50033V5.00033C2.5835 3.89576 3.47893 3.00033 4.5835 3.00033L5.91683 3.00033V2.29199C5.91683 1.87778 6.25262 1.54199 6.66683 1.54199ZM6.66683 4.50033H4.5835C4.30735 4.50033 4.0835 4.72418 4.0835 5.00033V6.75033H15.9168V5.00033C15.9168 4.72418 15.693 4.50033 15.4168 4.50033H13.3335H6.66683ZM15.9168 8.25033H4.0835V15.8337C4.0835 16.1098 4.30735 16.3337 4.5835 16.3337H15.4168C15.693 16.3337 15.9168 16.1098 15.9168 15.8337V8.25033Z" fill="" />
                        </svg>
                    </div>
                </div>

                <button
                    x-show="fromDate && toDate"
                    @click="clearSearch()"
                    type="button"
                    title="Clear date filter"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 px-3 text-theme-xs font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">
                    Clear
                </button>
            </div>
        </div>

        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <table class="w-full min-w-[1100px]">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">ID</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Customer</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Phone Number</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Template</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Message</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Sent At</p></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="log in logs" :key="log.id">
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-5 py-4 sm:px-6"><span class="text-gray-500 text-theme-sm dark:text-gray-400" x-text="log.id"></span></td>
                            <td class="px-5 py-4 sm:px-6"><span class="font-medium text-gray-800 text-theme-sm dark:text-white/90" x-text="log.customerName"></span></td>
                            <td class="px-5 py-4 sm:px-6"><p class="text-gray-500 text-theme-sm dark:text-gray-400" x-text="log.phone"></p></td>
                            <td class="px-5 py-4 sm:px-6"><p class="text-gray-500 text-theme-sm dark:text-gray-400" x-text="log.templateName"></p></td>
                            <td class="px-5 py-4 sm:px-6"><p class="max-w-[280px] truncate text-gray-500 text-theme-sm dark:text-gray-400" :title="log.message" x-text="log.message"></p></td>
                            <td class="px-5 py-4 sm:px-6"><p class="text-theme-xs inline-block rounded-full px-2 py-0.5 font-medium" :class="getStatusClass(log.status)" x-text="statusLabel(log.status)"></p></td>
                            <td class="px-5 py-4 sm:px-6"><p class="text-gray-700 text-theme-sm dark:text-gray-300" x-text="log.sentAt"></p></td>
                        </tr>
                    </template>

                    <!-- Empty state -->
                    <tr x-show="logs.length === 0">
                        <td colspan="7" class="px-5 py-4">
                            <div class="flex h-[300px] flex-col items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" class="text-gray-300 dark:text-gray-600">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <p class="text-theme-sm font-medium text-gray-500 dark:text-gray-400">No SMS logs for that date</p>
                                <p class="text-theme-xs text-gray-400 dark:text-gray-500">Try selecting a different date range.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-100 px-5 py-4 dark:border-gray-800 sm:flex-row sm:px-6">
            <p class="text-theme-xs text-gray-500 dark:text-gray-400" x-show="total > 0">
                Showing <span x-text="from"></span> to <span x-text="to"></span> of <span x-text="total"></span> results
            </p>
            <p class="text-theme-xs text-gray-500 dark:text-gray-400" x-show="total === 0">No results</p>

            <div class="flex items-center gap-1" x-show="lastPage > 1">
                <button
                    @click="goToPage(currentPage - 1)"
                    :disabled="currentPage === 1"
                    type="button"
                    class="inline-flex h-8 items-center justify-center rounded-lg border border-gray-300 px-3 text-theme-xs font-medium text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">
                    Prev
                </button>

                <template x-for="p in pageNumbers" :key="p">
                    <button
                        @click="goToPage(p)"
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-theme-xs font-medium transition"
                        :class="p === currentPage
                            ? 'bg-brand-500 text-white'
                            : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/[0.05]'"
                        x-text="p">
                    </button>
                </template>

                <button
                    @click="goToPage(currentPage + 1)"
                    :disabled="currentPage === lastPage"
                    type="button"
                    class="inline-flex h-8 items-center justify-center rounded-lg border border-gray-300 px-3 text-theme-xs font-medium text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">
                    Next
                </button>
            </div>
        </div>
    </div>
</div>