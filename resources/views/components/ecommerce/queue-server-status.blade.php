{{-- Who is working and what each barber is doing right now. --}}
<div x-data="{
        barbers: [],
        tickets: [],
        loaded: false,
        now: Date.now(),
        get onDuty() { return this.barbers.filter(b => b.is_active).length; },
        get rows() {
            return this.barbers
                .map(barber => {
                    const ticket = this.tickets.find(t => t.status === 'serving' && t.barber_id === barber.id);
                    return { ...barber, ticket };
                })
                .sort((a, b) => (b.is_active - a.is_active) || a.name.localeCompare(b.name));
        },
        async refresh() {
            try {
                const [barberRes, queueRes] = await Promise.all([
                    fetch('/api/barbers', { cache: 'no-store', headers: { Accept: 'application/json' } }),
                    fetch('/api/queue', { cache: 'no-store', headers: { Accept: 'application/json' } }),
                ]);
                if (barberRes.ok) this.barbers = await barberRes.json();
                if (queueRes.ok) this.tickets = await queueRes.json();
                this.now = Date.now();
            } finally {
                this.loaded = true;
            }
        },
        init() {
            this.refresh();
            const timer = setInterval(() => this.refresh(), 10000);
            window.addEventListener('beforeunload', () => clearInterval(timer), { once: true });
        }
    }"
    class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-start justify-between gap-3 px-5 pt-5 sm:px-6 sm:pt-6">
        <div>
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg">Server Status</h3>
            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400 sm:text-theme-sm" x-show="loaded">
                <span class="font-medium tabular-nums text-gray-700 dark:text-gray-300" x-text="onDuty"></span> of
                <span class="tabular-nums" x-text="barbers.length"></span> barbers on duty
            </p>
        </div>
        <a href="/queue#barbers" class="text-theme-xs font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400">Manage</a>
    </div>

    <div x-show="loaded && barbers.length && onDuty === 0" x-cloak
        class="mx-5 mt-4 rounded-lg bg-warning-50 px-3 py-2 text-theme-xs text-warning-700 dark:bg-warning-500/10 dark:text-warning-400 sm:mx-6">
        No barbers are on duty, so the queue is paused.
    </div>

    <ul class="custom-scrollbar mt-2 max-h-[320px] flex-1 divide-y divide-gray-100 overflow-y-auto px-5 pb-3 dark:divide-gray-800 sm:px-6">
        <template x-for="barber in rows" :key="barber.id">
            <li class="flex items-center gap-3 py-3">
                <template x-if="barber.image">
                    <img :src="barber.image" :alt="barber.name" class="h-10 w-10 shrink-0 rounded-full object-cover" />
                </template>
                <template x-if="!barber.image">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-theme-xs font-semibold"
                        :class="barber.is_active ? sc.tone(barber.name) : 'bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500'"
                        x-text="sc.initials(barber.name)"></span>
                </template>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-theme-sm font-medium" :class="barber.is_active ? 'text-gray-800 dark:text-white/90' : 'text-gray-400 dark:text-gray-500'" x-text="barber.name"></p>
                    <p class="truncate text-theme-xs text-gray-500 dark:text-gray-400" x-text="barber.role"></p>
                </div>
                <p class="shrink-0 text-right text-theme-xs">
                    <template x-if="barber.ticket">
                        <span class="text-gray-700 dark:text-gray-300">
                            <span class="font-mono font-semibold" x-text="sc.ticket(barber.ticket.queue_number)"></span>
                            <span class="block tabular-nums text-gray-400 dark:text-gray-500" x-text="sc.duration(sc.minutesSince(barber.ticket.served_at, now)) + ' in chair'"></span>
                        </span>
                    </template>
                    <template x-if="!barber.ticket">
                        <span class="text-theme-xs inline-block whitespace-nowrap rounded-full px-2 py-0.5 font-medium" :class="barber.is_active ? 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-500' : 'bg-gray-100 text-gray-500 dark:bg-gray-500/15 dark:text-gray-400'" x-text="barber.is_active ? 'Free' : 'Off duty'"></span>
                    </template>
                </p>
            </li>
        </template>
    </ul>

    <p x-show="loaded && barbers.length === 0" class="px-5 pb-6 pt-2 text-theme-sm text-gray-500 dark:text-gray-400 sm:px-6">No barbers added yet.</p>

    <div class="flex items-center gap-6 border-t border-gray-100 px-5 py-4 dark:border-gray-800 sm:px-6">
        <div>
            <p class="text-theme-xs text-gray-500 dark:text-gray-400">Waiting</p>
            <p class="mt-0.5 text-base font-semibold tabular-nums text-gray-800 dark:text-white/90" x-text="tickets.filter(t => t.status === 'in_queue').length"></p>
        </div>
        <div class="h-8 w-px bg-gray-200 dark:bg-gray-800"></div>
        <div>
            <p class="text-theme-xs text-gray-500 dark:text-gray-400">In the chair</p>
            <p class="mt-0.5 text-base font-semibold tabular-nums text-gray-800 dark:text-white/90" x-text="tickets.filter(t => t.status === 'serving').length"></p>
        </div>
    </div>
</div>
