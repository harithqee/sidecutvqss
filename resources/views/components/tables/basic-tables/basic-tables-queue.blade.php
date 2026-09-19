<div x-data="{
    orders: [],
    toast: { show: false, message: '', type: 'success' },

    showToast(message, type) {
        this.toast.message = message;
        this.toast.type = type || 'success';
        this.toast.show = true;
    },

    async init() {
        await this.loadOrders();
    },

    async loadOrders() {
        const res = await fetch('/api/queue');
        const tickets = await res.json();
        this.orders = tickets.map(function(t) {
            return {
                id: t.id,
                user: {
                    image: (t.barber && t.barber.image) || './images/user/user-17.jpg',
                    name: t.customer_name,
                    role: (t.service && t.service.name) || ''
                },
                queueNumber: '#' + t.queue_number,
                status: t.status
            };
        });
    },

    statusLabel(status) {
        const labels = { serving: 'Serving', in_queue: 'In Queue', canceled: 'Canceled', completed: 'Completed' };
        return labels[status] || status;
    },

    getStatusClass(status) {
        const classes = {
            serving: 'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-400',
            in_queue: 'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400',
            canceled: 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-500',
            completed: 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-500'
        };
        return classes[status] || '';
    },

    async updateStatus(order, status) {
        const res = await fetch('/api/queue/' + order.id + '/status', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ status: status })
        });
        if (!res.ok) {
            this.showToast('Could not update status.', 'error');
            return;
        }
        this.orders = this.orders.filter(function(o) {
            return o.id !== order.id;
        });
        this.showToast(status === 'completed' ? 'Ticket marked as completed.' : 'Ticket canceled.', 'success');
    },

    completeOrder(order) {
        this.updateStatus(order, 'completed');
    },

    cancelOrder(order) {
        this.updateStatus(order, 'canceled');
    },

    async sendSms(order) {
        const res = await fetch('/api/queue/' + order.id + '/sms', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({})
        });
        if (!res.ok) {
            this.showToast('Could not send SMS.', 'error');
            return;
        }
        this.showToast('SMS sent to ' + order.user.name + '.', 'success');
    }
}">

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
        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <table class="w-full min-w-[1150px]">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">ID</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Customer</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Queue Number</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Actions</p></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="order in orders" :key="order.id">
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-5 py-4 sm:px-6"><span class="text-gray-500 text-theme-sm dark:text-gray-400" x-text="order.id"></span></td>
                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 overflow-hidden rounded-full"><img :src="order.user.image" :alt="order.user.name"></div>
                                    <div>
                                        <span class="block font-medium text-gray-800 text-theme-sm dark:text-white/90" x-text="order.user.name"></span>
                                        <span class="block text-gray-500 text-theme-xs dark:text-gray-400" x-text="order.user.role"></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 sm:px-6"><p class="text-gray-500 text-theme-sm dark:text-gray-400" x-text="order.queueNumber"></p></td>
                            <td class="px-5 py-4 sm:px-6"><p class="text-theme-xs inline-block rounded-full px-2 py-0.5 font-medium" :class="getStatusClass(order.status)" x-text="statusLabel(order.status)"></p></td>
                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center gap-2">
                                    <button @click="sendSms(order)" type="button"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-xs font-medium text-gray-700 shadow-theme-xs transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
                                            <path d="M0 0h24v24H0z" fill="none" />
                                            <path fill="currentColor" fill-rule="evenodd" d="M5.788 14.02a1 1 0 0 0 .132.031a456 456 0 0 1 .844 2.002c.503 1.202 1.01 2.44 1.121 2.796c.139.438.285.736.445.94c.083.104.178.196.29.266a1 1 0 0 0 .186.088c.32.12.612.07.795.009a1.3 1.3 0 0 0 .304-.15L9.91 20l2.826-1.762l3.265 2.502q.072.055.156.093c.392.17.772.23 1.13.182c.356-.05.639-.199.85-.368a2 2 0 0 0 .564-.728l.009-.022l.003-.008l.002-.004v-.002l.001-.001a1 1 0 0 0 .04-.133l2.98-15.025a1 1 0 0 0 .014-.146c0-.44-.166-.859-.555-1.112c-.334-.217-.705-.227-.94-.209c-.252.02-.486.082-.643.132a4 4 0 0 0-.26.094l-.011.005l-16.714 6.556l-.002.001a2 2 0 0 0-.167.069a2.5 2.5 0 0 0-.38.212c-.227.155-.75.581-.661 1.285c.07.56.454.905.689 1.071c.128.091.25.156.34.199c.04.02.126.054.163.07l.01.003zm14.138-9.152h-.002l-.026.011l-16.734 6.565l-.026.01l-.01.003a1 1 0 0 0-.09.04a1 1 0 0 0 .086.043l3.142 1.058a1 1 0 0 1 .16.076l10.377-6.075l.01-.005a2 2 0 0 1 .124-.068c.072-.037.187-.091.317-.131c.09-.028.357-.107.645-.014a.85.85 0 0 1 .588.689a.84.84 0 0 1 .003.424c-.07.275-.262.489-.437.653c-.15.14-2.096 2.016-4.015 3.868l-2.613 2.52l-.465.45l5.872 4.502a.54.54 0 0 0 .251.04a.23.23 0 0 0 .117-.052a.5.5 0 0 0 .103-.12l.002-.001l2.89-14.573a2 2 0 0 0-.267.086zm-8.461 12.394l-1.172-.898l-.284 1.805zm-2.247-2.68l1.165-1.125l2.613-2.522l.973-.938l-6.52 3.817l.035.082a339 339 0 0 1 1.22 2.92l.283-1.8a.75.75 0 0 1 .231-.435" clip-rule="evenodd" />
                                        </svg>
                                        SMS
                                    </button>
                                    <button @click="completeOrder(order)" type="button" :disabled="order.status === 'completed' || order.status === 'canceled'"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-theme-xs font-medium text-white shadow-theme-xs transition hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-400 dark:disabled:bg-gray-700 dark:disabled:text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        Complete
                                    </button>
                                    <button @click="cancelOrder(order)" type="button" :disabled="order.status === 'completed' || order.status === 'canceled'"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-2 text-theme-xs font-medium text-white shadow-theme-xs transition hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-400 dark:disabled:bg-gray-700 dark:disabled:text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        Cancel
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>