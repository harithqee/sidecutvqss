@extends('layouts.app')

@section('content')
    <div x-data="barberQueueControl()" x-init="init()" @keydown.escape.window="closeOverlays()" class="space-y-6">
        <x-common.page-breadcrumb pageTitle="Queue Control" />

        <!-- Live join alert (small, non-blocking) -->
        <div x-show="toast.show" x-cloak style="display: none;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="fixed right-5 top-5 z-[100000] w-full max-w-sm rounded-xl border border-gray-200 bg-white p-4 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900"
             role="status" aria-live="polite">
            <div class="flex items-start gap-3">
                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-success-500"></span>
                <div class="min-w-0 flex-1">
                    <p class="text-theme-sm font-semibold text-gray-800 dark:text-white/90" x-text="toast.title"></p>
                    <p class="mt-0.5 text-theme-xs text-gray-500 dark:text-gray-400" x-text="toast.message"></p>
                </div>
                <button @click="toast.show = false" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Dismiss alert">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        </div>

        <!-- Action result popup -->
        <div x-show="popup.show" x-cloak style="display: none;" @click.self="popup.show = false"
             class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/40 px-4" role="dialog" aria-modal="true">
            <div x-show="popup.show"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="w-full max-w-sm rounded-xl bg-white p-6 text-center shadow-theme-lg dark:bg-gray-900">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full"
                     :class="{ 'bg-green-50 dark:bg-green-500/15': popup.type === 'success', 'bg-red-50 dark:bg-red-500/15': popup.type === 'error', 'bg-yellow-50 dark:bg-yellow-500/15': popup.type === 'warning' }">
                    <svg x-show="popup.type === 'success'" class="text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <svg x-show="popup.type === 'error'" class="text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <svg x-show="popup.type === 'warning'" class="text-yellow-600 dark:text-yellow-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/></svg>
                </div>
                <h3 class="mb-1 text-base font-semibold text-gray-800 dark:text-white/90" x-text="popupHeading"></h3>
                <p class="mb-5 text-theme-sm text-gray-500 dark:text-gray-400" x-text="popup.message"></p>
                <button @click="popup.show = false" type="button" class="w-full rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600">OK</button>
            </div>
        </div>

        <!-- Cancel confirmation -->
        <div x-show="confirmCancel !== null" x-cloak style="display: none;" @click.self="confirmCancel = null"
             class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/40 px-4" role="dialog" aria-modal="true">
            <div x-show="confirmCancel !== null"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="w-full max-w-sm rounded-xl bg-white p-6 text-center shadow-theme-lg dark:bg-gray-900">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-50 dark:bg-red-500/15">
                    <svg class="text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <h3 class="mb-1 text-base font-semibold text-gray-800 dark:text-white/90">Cancel this ticket?</h3>
                <p class="mb-5 text-theme-sm text-gray-500 dark:text-gray-400"
                   x-text="confirmCancel ? (ticketLabel(confirmCancel.queue_number) + ' · ' + confirmCancel.customer_name + ' will be removed from the queue.') : ''"></p>
                <div class="flex gap-3">
                    <button @click="confirmCancel = null" type="button" class="h-11 flex-1 rounded-lg border border-gray-300 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">Keep in queue</button>
                    <button @click="confirmAndCancel()" type="button" class="h-11 flex-1 rounded-lg bg-red-600 text-theme-sm font-medium text-white transition hover:bg-red-700">Yes, cancel ticket</button>
                </div>
            </div>
        </div>

        <!-- Header card -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Your customers</h3>
                    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Call the next customer, start their service, and mark them complete.</p>
                </div>
                <div class="flex items-center gap-2 self-start">
                    <div class="flex items-center gap-1.5 rounded-full border border-gray-200 px-2.5 py-1 text-theme-xs font-medium text-gray-600 dark:border-gray-700 dark:text-gray-300">
                        <span class="relative flex h-2 w-2">
                            <span x-show="!connectionError" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full" :class="connectionError ? 'bg-red-500' : 'bg-success-500'"></span>
                        </span>
                        <span x-text="connectionError ? 'Reconnecting…' : 'Live'"></span>
                    </div>
                    <span x-show="lastUpdated" class="text-theme-xs text-gray-400 dark:text-gray-500" x-text="'Updated ' + lastUpdated"></span>
                </div>
            </div>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1 sm:max-w-xs">
                    <label for="queue-barber" class="mb-1.5 block text-theme-xs font-medium text-gray-500 dark:text-gray-400">Barber</label>
                    <select id="queue-barber" x-model="selectedBarber" @change="localStorage.setItem('queue-control-barber', selectedBarber)"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="all">All barbers</option>
                        <template x-for="barber in barbers" :key="barber.id">
                            <option :value="String(barber.id)" x-text="barber.name + (barber.is_active ? '' : ' (inactive)')"></option>
                        </template>
                    </select>
                </div>

                <button type="button" @click="enableAlerts()"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border px-4 text-theme-sm font-medium transition"
                        :class="alertsEnabled
                            ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-400'
                            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                    <span x-text="alertsEnabled ? 'Join alerts on' : 'Turn on join alerts'"></span>
                </button>
            </div>

            <div x-show="!loading && !anyBarberActive" x-cloak class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-theme-xs text-red-600 dark:bg-red-500/15 dark:text-red-400">
                No servers are currently active. Queue is paused.
            </div>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 gap-4 md:gap-6 xl:grid-cols-4">
            <template x-for="card in summaryCards" :key="card.label">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
                    <span class="text-sm text-gray-500 dark:text-gray-400" x-text="card.label"></span>
                    <div class="mt-2 flex items-end justify-between">
                        <h4 class="font-bold text-gray-800 text-title-sm dark:text-white/90" x-text="card.value"></h4>
                        <span x-show="card.pill" class="rounded-full px-2 py-0.5 text-xs font-medium" :class="card.pillClass" x-text="card.pill"></span>
                    </div>
                    <p class="mt-1 truncate text-theme-xs text-gray-400 dark:text-gray-500" x-text="card.hint"></p>
                </div>
            </template>
        </div>

        <!-- Tickets -->
        <div class="space-y-4">
            <template x-for="ticket in visibleTickets" :key="ticket.id">
                <div>
                    <div x-show="isFirstOfStatus(ticket)" class="mb-3 mt-2 flex items-center gap-2">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90" x-text="ticket.status === 'serving' ? 'Now serving' : 'Waiting'"></h3>
                        <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium" :class="statusClass(ticket.status)" x-text="sectionCount(ticket)"></span>
                    </div>

                    <article class="rounded-2xl border bg-white p-5 dark:bg-white/[0.03] sm:p-6"
         :class="ticket.status === 'serving'
            ? 'border-gray-200 border-l-4 border-l-yellow-500 dark:border-gray-800 dark:border-l-yellow-500'
            : (ticket.is_calling ? 'border-brand-300 ring-2 ring-brand-500/20 dark:border-brand-500/40' : 'border-gray-200 dark:border-gray-800')">

    <!-- Ticket info -->
    <div class="flex items-center gap-4">
        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl text-xl font-extrabold"
             :class="statusClass(ticket.status)" x-text="ticketLabel(ticket.queue_number)"></div>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h4 class="truncate text-base font-semibold text-gray-800 dark:text-white/90" x-text="ticket.customer_name"></h4>
                <span class="inline-block rounded-full px-2 py-0.5 text-theme-xs font-medium" :class="statusClass(ticket.status)" x-text="statusLabel(ticket.status)"></span>
                <span x-show="ticket.id === nextUpId" class="rounded-full bg-gray-100 px-2 py-0.5 text-theme-xs font-medium text-gray-600 dark:bg-gray-500/15 dark:text-gray-300">Next up</span>
                <span x-show="ticket.is_calling" class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2 py-0.5 text-theme-xs font-medium text-blue-700 dark:bg-blue-500/15 dark:text-blue-400">
                    <span class="relative flex h-1.5 w-1.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                    </span>
                    Calling
                </span>
            </div>
            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                <span x-text="ticket.barber?.name || 'Any barber'"></span>
            </p>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-theme-xs text-gray-400 dark:text-gray-500">
                <span x-show="ticket.joined_at">Joined <span x-text="joinedTime(ticket.joined_at)"></span></span>
                <span x-show="ticket.status === 'in_queue'" class="rounded-full px-2 py-0.5 font-medium" :class="waitClass(waitMinutes(ticket))" x-text="'Waiting ' + waitMinutes(ticket) + ' min'"></span>
                <span x-show="ticket.status === 'serving' && ticket.served_at" class="rounded-full bg-yellow-50 px-2 py-0.5 font-medium text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-400" x-text="'In chair ' + serviceMinutes(ticket) + ' min'"></span>
            </div>
        </div>
    </div>

    <!-- Action bar -->
    <div class="mt-5 flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 dark:border-gray-800 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">

        <!-- Secondary actions -->
        <div class="grid grid-cols-2 gap-3 sm:flex sm:items-center">
            <button type="button" @click="sendSms(ticket)" :disabled="busy === ticket.id"
                    class="inline-flex h-12 w-full items-center justify-center gap-2.5 rounded-lg border border-gray-300 bg-white px-5 text-sm font-medium text-gray-700 shadow-theme-xs transition hover:bg-gray-50 active:scale-[.98] disabled:cursor-wait disabled:opacity-60 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/20 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05] sm:w-auto">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                <span x-text="busy === ticket.id ? 'Sending…' : 'Send SMS'"></span>
            </button>

            <button type="button" @click="cancelOrder(ticket)" :disabled="busy === ticket.id"
                    class="inline-flex h-12 w-full items-center justify-center rounded-lg border border-red-200 bg-white px-5 text-sm font-semibold text-red-600 transition hover:bg-red-50 active:scale-[.98] disabled:cursor-wait disabled:opacity-60 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-500/15 dark:border-red-500/30 dark:bg-gray-900 dark:text-red-400 dark:hover:bg-red-500/10 sm:w-auto">
                Cancel
            </button>
        </div>

        <!-- Primary actions -->
        <div class="flex flex-col gap-3 sm:ml-auto sm:flex-row sm:items-center">
            <template x-if="ticket.status === 'in_queue'">
                <button type="button" @click="callCustomer(ticket)" :disabled="busy === ticket.id"
                        class="inline-flex h-12 w-full items-center justify-center gap-2.5 rounded-lg bg-brand-500 px-6 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600 active:scale-[.98] disabled:cursor-wait disabled:opacity-60 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/25 sm:w-auto">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M8 5v14l11-7L8 5z" fill="currentColor"/></svg>
                    <span x-text="busy === ticket.id ? 'Calling…' : (ticket.is_calling ? 'Call again' : 'Call customer')"></span>
                </button>
            </template>

            <template x-if="ticket.status === 'in_queue'">
                <button type="button" @click="startServing(ticket)" :disabled="busy === ticket.id"
                        class="inline-flex h-12 w-full items-center justify-center gap-2.5 rounded-lg bg-yellow-500 px-6 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-yellow-600 active:scale-[.98] disabled:cursor-wait disabled:opacity-60 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-yellow-500/25 sm:w-auto">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M6 4l14 8-14 8V4z" fill="currentColor"/></svg>
                    <span x-text="busy === ticket.id ? 'Starting…' : 'Start service'"></span>
                </button>
            </template>

            <template x-if="ticket.status === 'serving'">
                <button type="button" @click="completeOrder(ticket)" :disabled="busy === ticket.id"
                        class="inline-flex h-12 w-full items-center justify-center gap-2.5 rounded-lg bg-green-600 px-6 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-green-700 active:scale-[.98] disabled:cursor-wait disabled:opacity-60 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-green-500/25 sm:w-auto">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6"/></svg>
                    <span x-text="busy === ticket.id ? 'Completing…' : 'Complete service'"></span>
                </button>
            </template>
        </div>
    </div>
</article>
                </div>
            </template>

            <!-- Empty state -->
            <div x-show="visibleTickets.length === 0" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex h-[300px] flex-col items-center justify-center gap-2 px-5 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" class="text-gray-300 dark:text-gray-600">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p class="text-theme-sm font-medium text-gray-500 dark:text-gray-400" x-text="emptyTitle"></p>
                    <p class="text-theme-xs text-gray-400 dark:text-gray-500" x-text="emptyHint"></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function barberQueueControl() {
            return {
                tickets: [], barbers: [], selectedBarber: 'all', loading: true, connectionError: false,
                busy: null, pollTimer: null, seenTicketIds: null, alertsEnabled: false, restoredBarber: false,
                now: Date.now(), lastUpdated: '', confirmCancel: null,
                popup: { show: false, message: '', type: 'success' }, popupTimer: null,
                toast: { show: false, title: '', message: '' }, toastTimer: null,

                get anyBarberActive() { return this.barbers.some(barber => barber.is_active); },
                get visibleTickets() {
                    // Customers with no barber preference are visible to every barber.
                    return this.tickets
                        .filter(ticket => this.selectedBarber === 'all' || !ticket.barber_id || String(ticket.barber_id) === this.selectedBarber)
                        .sort((a, b) => (a.status === b.status ? a.queue_number - b.queue_number : (a.status === 'serving' ? -1 : 1)));
                },
                get servingTickets() { return this.visibleTickets.filter(ticket => ticket.status === 'serving'); },
                get waitingTickets() { return this.visibleTickets.filter(ticket => ticket.status === 'in_queue'); },
                get waitingCount() { return this.waitingTickets.length; },
                get servingCount() { return this.servingTickets.length; },
                get nextUpTicket() { return this.waitingTickets[0] || null; },
                get nextUpId() { return this.nextUpTicket ? this.nextUpTicket.id : null; },
                get longestWait() {
                    return this.waitingTickets.length ? Math.max(...this.waitingTickets.map(ticket => this.waitMinutes(ticket))) : 0;
                },
                get summaryCards() {
                    const longest = this.longestWait;
                    const next = this.nextUpTicket;
                    return [
                        { label: 'Waiting', value: String(this.waitingCount), hint: 'Customers in line' },
                        { label: 'Being served', value: String(this.servingCount), hint: 'In the chair now' },
                        { label: 'Longest wait', value: longest + ' min', hint: 'Oldest waiting ticket', pill: longest >= 30 ? 'Long' : (longest >= 20 ? 'Busy' : 'Normal'), pillClass: this.waitClass(longest) },
                        { label: 'Next up', value: next ? this.ticketLabel(next.queue_number) : '—', hint: next ? next.customer_name : 'Nobody waiting' },
                    ];
                },
                get popupHeading() { return { success: 'Success', error: 'Failed', warning: 'Heads up' }[this.popup.type]; },
                get emptyTitle() {
                    if (this.loading) return 'Loading the queue…';
                    if (!this.anyBarberActive) return 'The shop is closed';
                    if (this.selectedBarber !== 'all' && this.tickets.length > 0) return 'No customers for this barber';
                    return 'No one in the queue right now';
                },
                get emptyHint() {
                    if (this.loading) return 'Fetching the latest tickets.';
                    if (!this.anyBarberActive) return 'Activate a server in Manage Servers to start taking customers.';
                    if (this.selectedBarber !== 'all' && this.tickets.length > 0) return 'Switch to All barbers to see the rest of the queue.';
                    return 'New customers will appear here automatically.';
                },

                async init() {
                    this.alertsEnabled = localStorage.getItem('queue-control-alerts') === 'on';
                    await this.refresh();
                    this.pollTimer = window.setInterval(() => this.refresh(), 3000);
                    window.addEventListener('beforeunload', () => window.clearInterval(this.pollTimer), { once: true });
                },
                async refresh() {
                    try {
                        const [queueResponse, barberResponse] = await Promise.all([
                            fetch('/api/queue', { cache: 'no-store', headers: { Accept: 'application/json' } }),
                            fetch('/api/barbers', { cache: 'no-store', headers: { Accept: 'application/json' } })
                        ]);
                        if (!queueResponse.ok || !barberResponse.ok) throw new Error('Queue refresh failed');
                        const nextTickets = await queueResponse.json();
                        this.barbers = await barberResponse.json();
                        if (!this.restoredBarber) {
                            this.restoredBarber = true;
                            const savedBarber = localStorage.getItem('queue-control-barber');
                            if (savedBarber && this.barbers.some(barber => String(barber.id) === savedBarber)) this.selectedBarber = savedBarber;
                        }
                        const nextIds = new Set(nextTickets.map(ticket => String(ticket.id)));
                        if (this.seenTicketIds) {
                            nextTickets.filter(ticket => !this.seenTicketIds.has(String(ticket.id))).forEach(ticket => this.notifyJoin(ticket));
                        }
                        this.seenTicketIds = nextIds;
                        this.tickets = nextTickets;
                        this.now = Date.now();
                        this.lastUpdated = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        this.connectionError = false;
                    } catch (error) {
                        this.connectionError = true;
                    } finally { this.loading = false; }
                },

                // ---- Alerts & feedback ----
                async enableAlerts() {
                    this.alertsEnabled = !this.alertsEnabled;
                    localStorage.setItem('queue-control-alerts', this.alertsEnabled ? 'on' : 'off');
                    if (this.alertsEnabled && 'Notification' in window && Notification.permission === 'default') {
                        try { await Notification.requestPermission(); } catch (error) { /* In-page alerts remain enabled. */ }
                    }
                    if (this.alertsEnabled && navigator.vibrate) navigator.vibrate(80);
                    this.showToast(this.alertsEnabled ? 'Alerts are on' : 'Alerts are off', this.alertsEnabled ? 'You will be notified when a customer joins.' : 'You can still see new customers in the live queue.');
                },
                notifyJoin(ticket) {
                    const barberMatches = this.selectedBarber === 'all' || !ticket.barber_id || String(ticket.barber_id) === this.selectedBarber;
                    if (!barberMatches) return;
                    this.showToast('New customer joined', `${ticket.customer_name} · ${this.ticketLabel(ticket.queue_number)}`);
                    if (!this.alertsEnabled) return;
                    if (navigator.vibrate) navigator.vibrate([120, 60, 120]);
                    if ('Notification' in window && Notification.permission === 'granted') {
                        try { new Notification('New queue customer', { body: `${ticket.customer_name} ${this.ticketLabel(ticket.queue_number)} joined your queue.` }); } catch (error) { /* The in-page alert is still shown. */ }
                    }
                },
                showToast(title, message = '') {
                    this.toast.title = title; this.toast.message = message; this.toast.show = true;
                    window.clearTimeout(this.toastTimer);
                    this.toastTimer = window.setTimeout(() => { this.toast.show = false; }, 5000);
                },
                notify(message, type = 'success') {
                    this.popup = { show: true, message, type };
                    window.clearTimeout(this.popupTimer);
                    if (type === 'success') {
                        this.popupTimer = window.setTimeout(() => { this.popup.show = false; }, 2500);
                    }
                },
                closeOverlays() { this.popup.show = false; this.confirmCancel = null; this.toast.show = false; },

                // ---- Actions ----
                async callCustomer(ticket) {
                    this.busy = ticket.id;
                    try {
                        const response = await fetch(`/api/queue/${ticket.id}/call`, { method: 'POST', headers: { Accept: 'application/json' } });
                        const result = await response.json();
                        if (!response.ok) {
                            this.notify(result.message || 'Could not call customer. Please try again.', 'error');
                            return;
                        }
                        this.tickets.forEach(customer => { customer.is_calling = false; });
                        ticket.is_calling = true;
                        ticket.called_at = result.called_at;
                        ticket.call_version = result.call_version;
                        this.notify(`Ticket ${this.ticketLabel(ticket.queue_number)} called to the counter. Status stays In Queue.`);
                    } catch (error) { this.notify('Could not reach the queue. Please try again.', 'error'); }
                    finally { this.busy = null; }
                },
                async updateStatus(ticket, status) {
                    this.busy = ticket.id;
                    try {
                        const response = await fetch(`/api/queue/${ticket.id}/status`, { method: 'PATCH', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ status }) });
                        const result = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            this.notify('Could not update status. Please try again.', 'error');
                            return;
                        }
                        if (status === 'serving') {
                            ticket.status = 'serving';
                            ticket.is_calling = false;
                            ticket.served_at = ticket.served_at || new Date().toISOString();
                            this.notify('Customer is now being served.');
                            return;
                        }
                        this.tickets = this.tickets.filter(customer => customer.id !== ticket.id);
                        if (status === 'completed') {
                            if (result.sms_status === 'failed') this.notify('Ticket completed, but the SMS receipt failed to send.', 'warning');
                            else if (result.sms_status === 'skipped') this.notify('Ticket completed. SMS receipt not sent because the template is inactive.', 'warning');
                            else this.notify('Ticket completed and SMS receipt sent.');
                        } else {
                            this.notify('Ticket canceled and removed from the queue.');
                        }
                    } catch (error) { this.notify('Could not update status. Please try again.', 'error'); }
                    finally { this.busy = null; }
                },
                async sendSms(ticket) {
                    this.busy = ticket.id;
                    try {
                        const response = await fetch(`/api/queue/${ticket.id}/sms`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                            body: JSON.stringify({})
                        });
                        const result = await response.json();
                        if (!response.ok) {
                            this.notify(result.message || 'Could not send SMS. Please try again.', 'error');
                            return;
                        }
                        this.notify(`SMS sent to ${ticket.customer_name}.`);
                    } catch (error) {
                        this.notify('Could not send SMS. Please try again.', 'error');
                    } finally { this.busy = null; }
                },
                startServing(ticket) { this.updateStatus(ticket, 'serving'); },
                completeOrder(ticket) { this.updateStatus(ticket, 'completed'); },
                cancelOrder(ticket) { this.confirmCancel = ticket; },
                confirmAndCancel() {
                    const ticket = this.confirmCancel;
                    this.confirmCancel = null;
                    if (ticket) this.updateStatus(ticket, 'canceled');
                },

                // ---- Display helpers ----
                ticketLabel(number) { return `#${String(number).padStart(3, '0')}`; },
                joinedTime(value) { return new Date(value).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); },
                waitMinutes(ticket) { return Math.max(0, Math.floor((this.now - new Date(ticket.joined_at).getTime()) / 60000)); },
                serviceMinutes(ticket) { return Math.max(0, Math.floor((this.now - new Date(ticket.served_at).getTime()) / 60000)); },
                waitClass(minutes) {
                    if (minutes >= 30) return 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-400';
                    if (minutes >= 20) return 'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-400';
                    return 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-500';
                },
                statusClass(status) {
                    return status === 'serving'
                        ? 'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-400'
                        : 'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400';
                },
                statusLabel(status) { return status === 'serving' ? 'Serving' : 'In Queue'; },
                isFirstOfStatus(ticket) {
                    const list = this.visibleTickets;
                    const index = list.findIndex(item => item.id === ticket.id);
                    return index === 0 || list[index - 1].status !== ticket.status;
                },
                sectionCount(ticket) { return ticket.status === 'serving' ? this.servingCount : this.waitingCount; }
            };
        }
    </script>
@endsection
