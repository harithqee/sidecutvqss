<div x-data="{
    orders: [],
    barbers: [],
    selectedBarber: 'all',
    loaded: false,
    now: Date.now(),

    get filteredOrders() {
        if (this.selectedBarber === 'all') return this.orders;
        if (this.selectedBarber === 'unassigned') return this.orders.filter(order => !order.barberId);
        return this.orders.filter(order => String(order.barberId) === String(this.selectedBarber));
    },

    async init() {
        await this.loadOrders();
        const timer = setInterval(() => this.loadOrders(), 5000);
        window.addEventListener('beforeunload', () => clearInterval(timer), { once: true });
    },

    async loadOrders() {
        try {
            const [res, barberRes] = await Promise.all([
                fetch('/api/queue', { cache: 'no-store', headers: { Accept: 'application/json' } }),
                fetch('/api/barbers', { cache: 'no-store', headers: { Accept: 'application/json' } })
            ]);
            if (!res.ok || !barberRes.ok) return;
            const tickets = await res.json();
            this.barbers = await barberRes.json();
            this.orders = tickets.map(t => ({
                id: t.id,
                barberId: t.barber_id,
                name: t.customer_name,
                phone: t.customer_phone,
                queueNumber: t.queue_number,
                server: (t.barber && t.barber.name) || null,
                status: t.status,
                isCalling: !!t.is_calling,
                joinedAt: t.joined_at,
                servedAt: t.served_at,
            }));
            this.now = Date.now();
        } finally {
            this.loaded = true;
        }
    },

    statusLabel(order) {
        if (order.status === 'serving') return 'In chair';
        return order.isCalling ? 'Called' : 'Waiting';
    },

    statusClass(order) {
        return order.status === 'serving' ? 'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-400' : 'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400';
    },

    elapsed(order) {
        return order.status === 'serving'
            ? sc.minutesSince(order.servedAt, this.now)
            : sc.minutesSince(order.joinedAt, this.now);
    },

    async updateStatus(order, status) {
        const res = await fetch('/api/queue/' + order.id + '/status', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ status: status })
        });
        const result = await res.json().catch(() => ({}));
        if (!res.ok) {
            Alpine.store('toast').push('Could not update status.', 'error');
            return;
        }
        if (status === 'serving') {
            order.status = 'serving';
            order.isCalling = false;
            order.servedAt = new Date().toISOString();
            Alpine.store('toast').push(order.name + ' is in the chair.');
        } else {
            this.orders = this.orders.filter(o => o.id !== order.id);
            if (status === 'completed') {
                if (result.sms_status === 'failed') Alpine.store('toast').push('Service finished, but the SMS receipt failed to send.', 'warning');
                else if (result.sms_status === 'skipped') Alpine.store('toast').push('Service finished. No receipt sent because that SMS template is turned off.', 'warning');
                else Alpine.store('toast').push('Service finished and receipt sent.');
            } else {
                Alpine.store('toast').push(sc.ticket(order.queueNumber) + ' was cancelled.');
            }
        }
    },

    async callCustomer(order) {
        try {
            const res = await fetch('/api/queue/' + order.id + '/call', {
                method: 'POST',
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (!res.ok) {
                Alpine.store('toast').push(data.message || 'Could not call this customer.', 'error');
                return;
            }
            this.orders.forEach(ticket => { ticket.isCalling = false; });
            order.isCalling = true;
            Alpine.store('toast').push(sc.ticket(order.queueNumber) + ' is on the calling board.');
        } catch (error) {
            Alpine.store('toast').push('Could not reach the queue. Please try again.', 'error');
        }
    },

    startServing(order) {
        this.updateStatus(order, 'serving');
    },

    completeOrder(order) {
        this.updateStatus(order, 'completed');
    },

    async cancelOrder(order) {
        const confirmed = await Alpine.store('confirm').ask({
            title: 'Cancel ' + sc.ticket(order.queueNumber) + '?',
            message: order.name + ' will be removed from the queue. This can\'t be undone.',
            confirmText: 'Cancel ticket',
            cancelText: 'Keep in queue',
        });
        if (confirmed) this.updateStatus(order, 'canceled');
    },

    async sendSms(order) {
        const res = await fetch('/api/queue/' + order.id + '/sms', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({})
        });
        if (!res.ok) {
            Alpine.store('toast').push('Could not send SMS.', 'error');
            return;
        }
        Alpine.store('toast').push('SMS sent to ' + order.name + '.');
    }
}">
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">
                <span class="font-semibold tabular-nums text-gray-800 dark:text-white/90" x-text="filteredOrders.filter(o => o.status === 'in_queue').length"></span> waiting,
                <span class="font-semibold tabular-nums text-gray-800 dark:text-white/90" x-text="filteredOrders.filter(o => o.status === 'serving').length"></span> in the chair
            </p>
            <label class="relative block sm:w-52">
                <span class="sr-only">Filter by barber</span>
                <select x-model="selectedBarber"
                    class="h-10 w-full appearance-none rounded-lg border border-gray-300 bg-white pl-3 pr-9 text-theme-sm text-gray-700 shadow-theme-xs outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    <option value="all">All barbers</option>
                    <template x-for="barber in barbers" :key="barber.id">
                        <option :value="String(barber.id)" x-text="barber.name"></option>
                    </template>
                    <option value="unassigned">No preference</option>
                </select>
                <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </label>
        </div>

        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <table class="w-full min-w-[1100px]">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-theme-xs font-medium text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        <th class="px-5 py-3 font-medium">Ticket</th>
                        <th class="px-5 py-3 font-medium">Customer</th>
                        <th class="px-5 py-3 font-medium">Barber</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Time</th>
                        <th class="px-5 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <template x-for="order in filteredOrders" :key="order.id">
                        <tr class="text-theme-sm">
                            <td class="px-5 py-3.5 font-mono font-semibold tabular-nums text-gray-900 dark:text-white">
                                <span class="text-gray-300 dark:text-gray-600">#</span><span x-text="String(order.queueNumber).padStart(3, '0')"></span>
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="font-medium text-gray-800 dark:text-white/90" x-text="order.name"></p>
                                <p class="text-theme-xs tabular-nums text-gray-500 dark:text-gray-400" x-text="order.phone"></p>
                            </td>
                            <td class="px-5 py-3.5 text-gray-600 dark:text-gray-300">
                                <span x-text="order.server || 'Any barber'" :class="order.server ? '' : 'text-gray-400 dark:text-gray-500'"></span>
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="text-theme-xs inline-block whitespace-nowrap rounded-full px-2 py-0.5 font-medium" :class="statusClass(order)" x-text="statusLabel(order)"></p>
                            </td>
                            <td class="px-5 py-3.5 tabular-nums text-gray-600 dark:text-gray-300">
                                <span x-text="sc.duration(elapsed(order))"></span>
                                <span class="text-theme-xs text-gray-400 dark:text-gray-500" x-text="order.status === 'serving' ? 'in chair' : 'waiting'"></span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <!-- Secondary: talk to the customer -->
                                    <button @click="sendSms(order)" type="button" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg px-3 text-theme-xs font-semibold text-white shadow-theme-xs transition bg-blue-light-600 hover:bg-blue-light-700" :aria-label="'Send SMS to ' + order.name">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                                        SMS
                                    </button>
                                    <button @click="callCustomer(order)" type="button" :disabled="order.status !== 'in_queue'"
                                        class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg px-3 text-theme-xs font-semibold text-white shadow-theme-xs transition w-[132px] bg-brand-500 hover:bg-brand-600 disabled:pointer-events-none disabled:opacity-40">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4V5Z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/></svg>
                                        <span x-text="order.isCalling ? 'Call again' : 'Call to counter'">Call to counter</span>
                                    </button>

                                    <!-- Primary: move the ticket forward -->
                                    <template x-if="order.status === 'in_queue'">
                                        <button @click="startServing(order)" type="button" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg px-3 text-theme-xs font-semibold text-white shadow-theme-xs transition w-[128px] bg-warning-600 hover:bg-warning-700">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4.5v15l12-7.5-12-7.5Z"/></svg>
                                            Start service
                                        </button>
                                    </template>
                                    <template x-if="order.status === 'serving'">
                                        <button @click="completeOrder(order)" type="button" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg px-3 text-theme-xs font-semibold text-white shadow-theme-xs transition w-[128px] bg-success-600 hover:bg-success-700">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                                            Complete
                                        </button>
                                    </template>

                                    <!-- Destructive, kept quiet until hovered -->
                                    <button @click="cancelOrder(order)" type="button"
                                        class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg px-3 text-theme-xs font-semibold text-white shadow-theme-xs transition bg-error-600 hover:bg-error-700">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                                        Cancel
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div x-show="loaded && filteredOrders.length === 0" class="px-5 py-14 text-center">
            <p class="text-theme-sm font-medium text-gray-700 dark:text-gray-300" x-text="orders.length ? 'Nobody waiting for this barber' : 'The line is empty'"></p>
            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">New customers show up here as soon as they join.</p>
        </div>
    </div>
</div>
