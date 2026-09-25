<div x-data="{
    history: [],
    fromDate: null,
    toDate: null,
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 10,
    from: 0,
    to: 0,
    toast: { show: false, message: '', type: 'success' },

    showToast(message, type) {
        this.toast.message = message;
        this.toast.type = type || 'success';
        this.toast.show = true;
    },

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
            const from = this.formatLocalDate(this.fromDate);
            const to = this.formatLocalDate(this.toDate);
            url += '&from=' + from + '&to=' + to;
        }
        const res = await fetch(url);
        const page_res = await res.json();
        this.history = page_res.data.map(function(t) {
            return {
                id: t.id,
                name: t.customer_name,
                phone: t.customer_phone,
                server: t.barber ? t.barber.name : '—',
                joinedAt: t.joined_at ? new Date(t.joined_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '—',
                waitingTime: t.waiting_time || '—',
                servedAt: t.served_at ? new Date(t.served_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '—',
                finishedAt: t.finished_at ? new Date(t.finished_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '—',
                date: t.session ? new Date(t.session.session_date).toDateString() : '',
                status: t.status === 'completed' ? 'Completed' : 'Canceled'
            };
        });
        this.currentPage = page_res.current_page;
        this.lastPage = page_res.last_page;
        this.total = page_res.total;
        this.perPage = page_res.per_page;
        this.from = page_res.from || 0;
        this.to = page_res.to || 0;
    },

    goToPage(page) {
        if (page < 1 || page > this.lastPage || page === this.currentPage) return;
        this.loadHistory(page);
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
        await this.loadHistory(1);
    },

    clearSearch() {
        this.fromDate = null;
        this.toDate = null;
        this.loadHistory(1);
    },

    getStatusClass(status) {
        const classes = {
            'Completed': 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-500',
            'Canceled': 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-500'
        };
        return classes[status] || '';
    },

    getWaitingTimeClass(entry) {
        if (entry.waitingTime === '—') return 'text-gray-400 dark:text-gray-500';
        const minutes = parseInt(entry.waitingTime);
        if (minutes >= 30) return 'text-red-600 dark:text-red-400 font-medium';
        if (minutes >= 20) return 'text-yellow-600 dark:text-yellow-400 font-medium';
        return 'text-green-600 dark:text-green-400 font-medium';
    },

    viewDetails(entry) {
        this.showToast(entry.phone + ' — ' + entry.name, 'success');
    },

    resendReceipt(entry) {
        this.showToast('Receipt resent to ' + entry.name + '.', 'success');
    }
}"
@date-range-changed.window="onDateRangeChange($event.detail.from, $event.detail.to)">

    <!-- Popup alert -->
    <div x-show="toast.show" x-cloak class="fixed inset-0 z-999 flex items-center justify-center bg-black/40 px-4" style="display: none;">
        <div x-show="toast.show"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             @click.away="toast.show = false"
             class="w-full max-w-sm rounded-xl bg-white p-6 text-center shadow-theme-lg dark:bg-gray-900">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full"
                 :class="toast.type === 'success' ? 'bg-green-50 dark:bg-green-500/15' : 'bg-red-50 dark:bg-red-500/15'">
                <svg x-show="toast.type === 'success'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-600 dark:text-green-400"/>
                </svg>
                <svg x-show="toast.type === 'error'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-600 dark:text-red-400"/>
                </svg>
            </div>
            <h3 class="mb-1 text-base font-semibold text-gray-800 dark:text-white/90" x-text="toast.type === 'success' ? 'Success' : 'Failed'"></h3>
            <p class="mb-5 text-theme-sm text-gray-500 dark:text-gray-400" x-text="toast.message"></p>
            <button @click="toast.show = false" type="button" class="w-full rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600">OK</button>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800 sm:px-6">
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg">Queue History</h3>
                <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400 sm:text-theme-sm">Recent customers served today.</p>
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
            <table class="w-full min-w-[1250px]">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">ID</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Customer</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Phone Number</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Server</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Joined Queue</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Waiting Time</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Served At</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Finish At</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Actions</p></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="entry in history" :key="entry.id">
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-5 py-4 sm:px-6"><span class="text-gray-500 text-theme-sm dark:text-gray-400" x-text="entry.id"></span></td>
                            <td class="px-5 py-4 sm:px-6"><span class="font-medium text-gray-800 text-theme-sm dark:text-white/90" x-text="entry.name"></span></td>
                            <td class="px-5 py-4 sm:px-6"><p class="text-gray-500 text-theme-sm dark:text-gray-400" x-text="entry.phone"></p></td>
                            <td class="px-5 py-4 sm:px-6"><p class="text-gray-500 text-theme-sm dark:text-gray-400" x-text="entry.server"></p></td>
                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex flex-col">
                                    <span class="text-gray-700 text-theme-sm dark:text-gray-300" x-text="entry.joinedAt"></span>
                                    <span class="text-gray-400 text-theme-xs dark:text-gray-500" x-text="entry.date"></span>
                                </div>
                            </td>
                            <td class="px-5 py-4 sm:px-6"><p class="text-theme-sm" :class="getWaitingTimeClass(entry)" x-text="entry.waitingTime"></p></td>
                            <td class="px-5 py-4 sm:px-6"><p class="text-gray-700 text-theme-sm dark:text-gray-300" x-text="entry.servedAt"></p></td>
                            <td class="px-5 py-4 sm:px-6"><p class="text-gray-700 text-theme-sm dark:text-gray-300" x-text="entry.finishedAt"></p></td>
                            <td class="px-5 py-4 sm:px-6"><p class="text-theme-xs inline-block rounded-full px-2 py-0.5 font-medium" :class="getStatusClass(entry.status)" x-text="entry.status"></p></td>
                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center gap-2">
                                    <button @click="viewDetails(entry)" type="button" title="View details"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white p-2 text-gray-600 shadow-theme-xs transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
                                        </svg>
                                    </button>
                                    <button @click="resendReceipt(entry)" type="button" :disabled="entry.status === 'Canceled'" title="Resend receipt"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white p-2 text-gray-600 shadow-theme-xs transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
                                            <path d="M0 0h24v24H0z" fill="none" />
                                            <path fill="currentColor" fill-rule="evenodd" d="M5.788 14.02a1 1 0 0 0 .132.031a456 456 0 0 1 .844 2.002c.503 1.202 1.01 2.44 1.121 2.796c.139.438.285.736.445.94c.083.104.178.196.29.266a1 1 0 0 0 .186.088c.32.12.612.07.795.009a1.3 1.3 0 0 0 .304-.15L9.91 20l2.826-1.762l3.265 2.502q.072.055.156.093c.392.17.772.23 1.13.182c.356-.05.639-.199.85-.368a2 2 0 0 0 .564-.728l.009-.022l.003-.008l.002-.004v-.002l.001-.001a1 1 0 0 0 .04-.133l2.98-15.025a1 1 0 0 0 .014-.146c0-.44-.166-.859-.555-1.112c-.334-.217-.705-.227-.94-.209c-.252.02-.486.082-.643.132a4 4 0 0 0-.26.094l-.011.005l-16.714 6.556l-.002.001a2 2 0 0 0-.167.069a2.5 2.5 0 0 0-.38.212c-.227.155-.75.581-.661 1.285c.07.56.454.905.689 1.071c.128.091.25.156.34.199c.04.02.126.054.163.07l.01.003zm14.138-9.152h-.002l-.026.011l-16.734 6.565l-.026.01l-.01.003a1 1 0 0 0-.09.04a1 1 0 0 0 .086.043l3.142 1.058a1 1 0 0 1 .16.076l10.377-6.075l.01-.005a2 2 0 0 1 .124-.068c.072-.037.187-.091.317-.131c.09-.028.357-.107.645-.014a.85.85 0 0 1 .588.689a.84.84 0 0 1 .003.424c-.07.275-.262.489-.437.653c-.15.14-2.096 2.016-4.015 3.868l-2.613 2.52l-.465.45l5.872 4.502a.54.54 0 0 0 .251.04a.23.23 0 0 0 .117-.052a.5.5 0 0 0 .103-.12l.002-.001l2.89-14.573a2 2 0 0 0-.267.086zm-8.461 12.394l-1.172-.898l-.284 1.805zm-2.247-2.68l1.165-1.125l2.613-2.522l.973-.938l-6.52 3.817l.035.082a339 339 0 0 1 1.22 2.92l.283-1.8a.75.75 0 0 1 .231-.435" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty state, held to the height of ~5 data rows -->
                    <tr x-show="history.length === 0">
                        <td colspan="10" class="px-5 py-4">
                            <div class="flex h-[300px] flex-col items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" class="text-gray-300 dark:text-gray-600">
                                    <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M3 9h18" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M8 2v4M16 2v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                                <p class="text-theme-sm font-medium text-gray-500 dark:text-gray-400">No data for that date</p>
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