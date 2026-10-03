@extends('layouts.app')

@section('content')
    <div x-data="barberQueueControl()" x-init="init()">
        <x-common.page-header title="Queue Control"
            description="Call the next customer, start their cut and finish up. The list refreshes every few seconds.">
            <x-slot:actions>
                <span class="inline-flex h-10 items-center gap-2 px-1 text-theme-xs font-medium text-gray-500 dark:text-gray-400" :title="lastUpdated ? 'Updated ' + lastUpdated : ''">
                    <span class="relative flex h-2 w-2">
                        <span x-show="!connectionError" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-400 opacity-60"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full" :class="connectionError ? 'bg-error-500' : 'bg-success-500'"></span>
                    </span>
                    <span x-text="connectionError ? 'Reconnecting…' : 'Live'">Live</span>
                </span>

                <label class="relative">
                    <span class="sr-only">Show customers for</span>
                    <select x-model="selectedBarber" @change="localStorage.setItem('queue-control-barber', selectedBarber)"
                        class="h-10 appearance-none rounded-lg border border-gray-300 bg-white pl-3 pr-9 text-theme-sm font-medium text-gray-700 shadow-theme-xs outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                        <option value="all">All barbers</option>
                        <template x-for="barber in barbers" :key="barber.id">
                            <option :value="String(barber.id)" x-text="barber.name + (barber.is_active ? '' : ' (off duty)')"></option>
                        </template>
                    </select>
                    <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </label>

                <button type="button" @click="enableAlerts()" :aria-pressed="alertsEnabled"
                    class="inline-flex h-10 items-center gap-2 rounded-lg border px-3 text-theme-sm font-medium transition"
                    :class="alertsEnabled
                        ? 'border-brand-200 bg-brand-50 text-brand-600 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300'
                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/5'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/><path x-show="!alertsEnabled" d="M3 3l18 18"/></svg>
                    <span x-text="alertsEnabled ? 'Alerts on' : 'Alerts off'">Alerts off</span>
                </button>
            </x-slot:actions>
        </x-common.page-header>

        <!-- Shop closed -->
        <div x-show="!loading && !anyBarberActive" x-cloak
            class="mb-6 flex flex-col gap-3 rounded-2xl border border-warning-200 bg-warning-50 px-5 py-4 dark:border-warning-500/30 dark:bg-warning-500/10 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-theme-sm font-semibold text-warning-800 dark:text-warning-300">The queue is paused</p>
                <p class="text-theme-sm text-warning-700 dark:text-warning-400">No barber is on duty, so customers can't join. Put someone on duty to open the queue.</p>
            </div>
            <a href="/queue#barbers" class="inline-flex h-10 shrink-0 items-center rounded-lg bg-white px-4 text-theme-sm font-medium text-warning-800 shadow-theme-xs ring-1 ring-warning-200 hover:bg-warning-25 dark:bg-transparent dark:text-warning-300 dark:ring-warning-500/40">Manage barbers</a>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">

            <!-- Focus column: what needs doing right now -->
            <div class="space-y-6 lg:col-span-5">

                <!-- Next up -->
                <section aria-labelledby="next-heading">
                    <h2 id="next-heading" class="mb-3 text-theme-sm font-semibold uppercase tracking-[0.08em] text-gray-500 dark:text-gray-400">Next up</h2>

                    <template x-if="nextUpTicket">
                        <article class="relative overflow-hidden rounded-2xl border-2 bg-white p-5 dark:bg-white/[0.03]"
                            :class="nextUpTicket.is_calling ? 'border-brand-500' : 'border-gray-900 dark:border-white/80'">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="font-mono text-5xl font-semibold leading-none tracking-tight tabular-nums text-gray-900 dark:text-white">
                                        <span class="text-gray-300 dark:text-gray-600">#</span><span x-text="pad(nextUpTicket.queue_number)"></span>
                                    </p>
                                    <h3 class="mt-3 truncate text-lg font-semibold text-gray-900 dark:text-white" x-text="nextUpTicket.customer_name"></h3>
                                    <p class="mt-0.5 text-theme-sm text-gray-500 dark:text-gray-400">
                                        <span x-text="nextUpTicket.barber?.name ? 'Asked for ' + nextUpTicket.barber.name : 'Any barber'"></span>
                                        · joined <span x-text="sc.time(nextUpTicket.joined_at)"></span>
                                    </p>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-theme-xs font-medium tabular-nums" :class="waitClass(waitMinutes(nextUpTicket))"
                                    x-text="'Waited ' + sc.duration(waitMinutes(nextUpTicket))"></span>
                            </div>

                            <p x-show="nextUpTicket.is_calling" class="mt-4 flex items-center gap-2 text-theme-sm font-medium text-brand-600 dark:text-brand-400">
                                <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-brand-500"></span></span>
                                On the calling board now
                            </p>

                            <div class="mt-5 grid grid-cols-2 gap-2">
                                <button type="button" @click="callCustomer(nextUpTicket)" :disabled="busy === nextUpTicket.id"
                                    class="inline-flex h-11 items-center justify-center gap-2 rounded-lg px-4 text-theme-sm font-semibold text-white shadow-theme-xs transition disabled:cursor-wait disabled:opacity-60 bg-brand-500 hover:bg-brand-600">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1Z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/></svg>
                                    <span x-text="busy === nextUpTicket.id ? 'Calling…' : (nextUpTicket.is_calling ? 'Call again' : 'Call to chair')"></span>
                                </button>
                                <button type="button" @click="startServing(nextUpTicket)" :disabled="busy === nextUpTicket.id"
                                    class="inline-flex h-11 items-center justify-center gap-2 rounded-lg px-4 text-theme-sm font-semibold text-white shadow-theme-xs transition disabled:cursor-wait disabled:opacity-60 bg-warning-600 hover:bg-warning-700">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M20 4 8.12 15.88M14.47 14.48 20 20M8.12 8.12 12 12"/></svg>
                                    Start service
                                </button>
                            </div>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <button type="button" @click="sendSms(nextUpTicket)" :disabled="busy === nextUpTicket.id"
                                    class="inline-flex h-11 items-center justify-center gap-2 rounded-lg px-4 text-theme-sm font-semibold text-white shadow-theme-xs transition disabled:cursor-wait disabled:opacity-60 bg-blue-light-600 hover:bg-blue-light-700">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                                    Send SMS update
                                </button>
                                <button type="button" @click="cancelOrder(nextUpTicket)" :disabled="busy === nextUpTicket.id"
                                    class="inline-flex h-11 items-center justify-center gap-2 rounded-lg px-4 text-theme-sm font-semibold text-white shadow-theme-xs transition disabled:cursor-wait disabled:opacity-60 bg-error-600 hover:bg-error-700">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                                    Cancel ticket
                                </button>
                            </div>
                        </article>
                    </template>

                    <div x-show="!nextUpTicket" class="rounded-2xl border border-dashed border-gray-300 px-5 py-10 text-center dark:border-gray-700">
                        <p class="text-theme-sm font-medium text-gray-700 dark:text-gray-300" x-text="emptyTitle"></p>
                        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400" x-text="emptyHint"></p>
                    </div>
                </section>

                <!-- In the chair -->
                <section aria-labelledby="serving-heading">
                    <div class="mb-3 flex items-baseline justify-between">
                        <h2 id="serving-heading" class="text-theme-sm font-semibold uppercase tracking-[0.08em] text-gray-500 dark:text-gray-400">In the chair</h2>
                        <span class="text-theme-xs text-gray-400 dark:text-gray-500" x-show="servingCount > 0" x-text="servingCount + ' being served'"></span>
                    </div>

                    <div class="space-y-3">
                        <template x-for="ticket in servingTickets" :key="ticket.id">
                            <article class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                                <div class="flex items-start gap-4">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                            <span x-text="ticket.barber?.name || 'Any barber'"></span>
                                            · started <span x-text="sc.time(ticket.served_at)"></span>
                                        </p>
                                        <h3 class="mt-1 truncate text-lg font-semibold text-gray-900 dark:text-white" x-text="ticket.customer_name"></h3>
                                    </div>
                                    <p class="shrink-0 text-right">
                                        <span class="block font-mono text-2xl font-semibold tabular-nums text-gray-900 dark:text-white"><span class="text-gray-300 dark:text-gray-600">#</span><span x-text="pad(ticket.queue_number)"></span></span>
                                        <span class="text-theme-xs font-medium tabular-nums"
                                            :class="serviceMinutes(ticket) >= 45 ? 'text-warning-600 dark:text-warning-400' : 'text-gray-500 dark:text-gray-400'"
                                            x-text="sc.duration(serviceMinutes(ticket)) + ' in chair'"></span>
                                    </p>
                                </div>

                                <div class="mt-4 flex items-center gap-2">
                                    <button type="button" @click="completeOrder(ticket)" :disabled="busy === ticket.id"
                                        class="inline-flex h-11 items-center justify-center gap-2 rounded-lg px-4 text-theme-sm font-semibold text-white shadow-theme-xs transition disabled:cursor-wait disabled:opacity-60 flex-1 bg-success-600 hover:bg-success-700">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                                        <span x-text="busy === ticket.id ? 'Finishing…' : 'Finish service'"></span>
                                    </button>
                                </div>
                                <div class="mt-2 grid grid-cols-2 gap-2">
                                    <button type="button" @click="sendSms(ticket)" :disabled="busy === ticket.id"
                                        class="inline-flex h-11 items-center justify-center gap-2 rounded-lg px-4 text-theme-sm font-semibold text-white shadow-theme-xs transition disabled:cursor-wait disabled:opacity-60 bg-blue-light-600 hover:bg-blue-light-700">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                                        Send SMS update
                                    </button>
                                    <button type="button" @click="cancelOrder(ticket)" :disabled="busy === ticket.id"
                                        class="inline-flex h-11 items-center justify-center gap-2 rounded-lg px-4 text-theme-sm font-semibold text-white shadow-theme-xs transition disabled:cursor-wait disabled:opacity-60 bg-error-600 hover:bg-error-700">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                                        Cancel ticket
                                    </button>
                                </div>
                            </article>
                        </template>

                        <div x-show="servingCount === 0" class="rounded-2xl border border-dashed border-gray-300 px-5 py-6 text-center dark:border-gray-700">
                            <p class="text-theme-sm text-gray-500 dark:text-gray-400">Nobody in the chair.</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- The rest of the line -->
            <section class="lg:col-span-7" aria-labelledby="waiting-heading">
                <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                    <header class="flex flex-wrap items-baseline justify-between gap-2 border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                        <h2 id="waiting-heading" class="text-base font-semibold text-gray-800 dark:text-white/90">
                            Waiting <span class="ml-1 font-normal tabular-nums text-gray-400" x-text="waitingCount"></span>
                        </h2>
                        <p class="text-theme-xs text-gray-500 dark:text-gray-400" x-show="waitingCount > 0">
                            Longest wait <span class="font-medium tabular-nums text-gray-700 dark:text-gray-300" x-text="sc.duration(longestWait)"></span>
                        </p>
                    </header>

                    <ol class="divide-y divide-gray-100 dark:divide-gray-800">
                        <template x-for="(ticket, index) in laterTickets" :key="ticket.id">
                            <li class="flex items-center gap-3 px-5 py-3.5 sm:gap-4">
                                <span class="w-5 shrink-0 text-right text-theme-xs tabular-nums text-gray-400 dark:text-gray-500" x-text="index + 2"></span>
                                <span class="w-14 shrink-0 font-mono text-base font-semibold tabular-nums text-gray-900 dark:text-white"><span class="text-gray-300 dark:text-gray-600">#</span><span x-text="pad(ticket.queue_number)"></span></span>
                                <div class="min-w-0 flex-1">
                                    <p class="flex items-center gap-2 truncate text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                        <span class="truncate" x-text="ticket.customer_name"></span>
                                        <span x-show="ticket.is_calling" class="text-theme-xs shrink-0 whitespace-nowrap rounded-full bg-blue-50 px-2 py-0.5 font-medium text-blue-700 dark:bg-blue-500/15 dark:text-blue-400">Called</span>
                                    </p>
                                    <p class="truncate text-theme-xs text-gray-500 dark:text-gray-400" x-text="(ticket.barber?.name || 'Any barber') + ' · joined ' + sc.time(ticket.joined_at)"></p>
                                </div>
                                <span class="hidden shrink-0 rounded-full px-2 py-0.5 text-theme-xs font-medium tabular-nums sm:inline-block" :class="waitClass(waitMinutes(ticket))" x-text="sc.duration(waitMinutes(ticket))"></span>

                                <button type="button" @click="sendSms(ticket)" :disabled="busy === ticket.id" :aria-label="'Send SMS update to ' + ticket.customer_name" title="Send SMS update"
                                    class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg px-3 text-theme-xs font-semibold text-white shadow-theme-xs transition disabled:opacity-60 bg-blue-light-600 hover:bg-blue-light-700">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                                    <span class="hidden sm:inline">SMS</span>
                                </button>
                                <button type="button" @click="cancelOrder(ticket)" :disabled="busy === ticket.id" :aria-label="'Cancel ticket for ' + ticket.customer_name" title="Cancel ticket"
                                    class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg px-3 text-theme-xs font-semibold text-white shadow-theme-xs transition disabled:opacity-60 bg-error-600 hover:bg-error-700">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                                    <span class="hidden sm:inline">Cancel</span>
                                </button>

                                <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false" class="relative shrink-0">
                                    <button type="button" @click="open = !open" :aria-expanded="open" :aria-label="'More actions for ' + ticket.customer_name"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>
                                    </button>
                                    <div x-show="open" x-cloak x-transition.origin.top.right
                                        class="absolute right-0 top-full z-20 mt-1 w-48 rounded-xl border border-gray-200 bg-white p-1.5 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
                                        <button type="button" @click="open = false; callCustomer(ticket)" class="flex w-full rounded-lg px-3 py-2 text-left text-theme-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5" x-text="ticket.is_calling ? 'Call again' : 'Call to chair'"></button>
                                        <button type="button" @click="open = false; startServing(ticket)" class="flex w-full rounded-lg px-3 py-2 text-left text-theme-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">Start service</button>
                                    </div>
                                </div>
                            </li>
                        </template>
                    </ol>

                    <p x-show="laterTickets.length === 0" class="px-5 py-10 text-center text-theme-sm text-gray-500 dark:text-gray-400"
                        x-text="nextUpTicket ? 'No one else is waiting.' : 'The line is empty.'"></p>
                </div>
            </section>
        </div>
    </div>

    <script>
        function barberQueueControl() {
            return {
                tickets: [], barbers: [], selectedBarber: 'all', loading: true, connectionError: false,
                busy: null, pollTimer: null, seenTicketIds: null, alertsEnabled: false, restoredBarber: false,
                now: Date.now(), lastUpdated: '',

                get anyBarberActive() { return this.barbers.some(barber => barber.is_active); },
                get visibleTickets() {
                    // Customers with no barber preference are visible to every barber.
                    return this.tickets
                        .filter(ticket => this.selectedBarber === 'all' || !ticket.barber_id || String(ticket.barber_id) === this.selectedBarber)
                        .sort((a, b) => a.queue_number - b.queue_number);
                },
                get servingTickets() { return this.visibleTickets.filter(ticket => ticket.status === 'serving'); },
                get waitingTickets() { return this.visibleTickets.filter(ticket => ticket.status === 'in_queue'); },
                get waitingCount() { return this.waitingTickets.length; },
                get servingCount() { return this.servingTickets.length; },
                get nextUpTicket() { return this.waitingTickets[0] || null; },
                get laterTickets() { return this.waitingTickets.slice(1); },
                get longestWait() {
                    return this.waitingTickets.length ? Math.max(...this.waitingTickets.map(ticket => this.waitMinutes(ticket))) : 0;
                },
                get emptyTitle() {
                    if (this.loading) return 'Loading the queue…';
                    if (!this.anyBarberActive) return 'The shop is closed';
                    if (this.selectedBarber !== 'all' && this.tickets.length > 0) return 'Nobody waiting for this barber';
                    return 'Nobody is waiting';
                },
                get emptyHint() {
                    if (this.loading) return 'Fetching the latest tickets.';
                    if (!this.anyBarberActive) return 'Put a barber on duty to start taking customers.';
                    if (this.selectedBarber !== 'all' && this.tickets.length > 0) return 'Switch to All barbers to see the rest of the line.';
                    return 'New customers appear here as soon as they join.';
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
                    this.notify(this.alertsEnabled ? 'You will be notified when a customer joins.' : 'New customers still appear in the list.', 'info');
                },
                notifyJoin(ticket) {
                    const barberMatches = this.selectedBarber === 'all' || !ticket.barber_id || String(ticket.barber_id) === this.selectedBarber;
                    if (!barberMatches) return;
                    Alpine.store('toast').push(`${ticket.customer_name} · ${sc.ticket(ticket.queue_number)}`, 'info', 'New customer joined');
                    if (!this.alertsEnabled) return;
                    if (navigator.vibrate) navigator.vibrate([120, 60, 120]);
                    if ('Notification' in window && Notification.permission === 'granted') {
                        try { new Notification('New queue customer', { body: `${ticket.customer_name} ${sc.ticket(ticket.queue_number)} joined your queue.` }); } catch (error) { /* The in-page alert is still shown. */ }
                    }
                },
                notify(message, type = 'success') {
                    Alpine.store('toast').push(message, type);
                },

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
                        this.notify(`${sc.ticket(ticket.queue_number)} is on the calling board.`);
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
                            this.notify(`${ticket.customer_name} is in the chair.`);
                            return;
                        }
                        this.tickets = this.tickets.filter(customer => customer.id !== ticket.id);
                        if (status === 'completed') {
                            if (result.sms_status === 'failed') this.notify('Service finished, but the SMS receipt failed to send.', 'warning');
                            else if (result.sms_status === 'skipped') this.notify('Service finished. No receipt sent because that SMS template is turned off.', 'warning');
                            else this.notify('Service finished and receipt sent.');
                        } else {
                            this.notify(`${sc.ticket(ticket.queue_number)} was cancelled.`);
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
                async cancelOrder(ticket) {
                    const confirmed = await Alpine.store('confirm').ask({
                        title: `Cancel ${sc.ticket(ticket.queue_number)}?`,
                        message: `${ticket.customer_name} will be removed from the queue. This can't be undone.`,
                        confirmText: 'Cancel ticket',
                        cancelText: 'Keep in queue',
                    });
                    if (confirmed) this.updateStatus(ticket, 'canceled');
                },

                // ---- Display helpers ----
                pad(number) { return String(number).padStart(3, '0'); },
                waitMinutes(ticket) { return sc.minutesSince(ticket.joined_at, this.now); },
                serviceMinutes(ticket) { return sc.minutesSince(ticket.served_at, this.now); },
                waitClass(minutes) {
                    if (minutes >= 30) return 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400';
                    if (minutes >= 20) return 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400';
                    return 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300';
                },
            };
        }
    </script>
@endsection
