{{-- Live shop status: replaces the template's promo card with something staff actually glance at. --}}
<div x-data="{
        barbers: [],
        tickets: [],
        loaded: false,
        failed: false,
        get onDuty() { return this.barbers.filter(b => b.is_active).length; },
        get waiting() { return this.tickets.filter(t => t.status === 'in_queue').length; },
        get serving() { return this.tickets.filter(t => t.status === 'serving').length; },
        get isOpen() { return this.onDuty > 0; },
        async refresh() {
            try {
                const [barberRes, queueRes] = await Promise.all([
                    fetch('/api/barbers', { cache: 'no-store', headers: { Accept: 'application/json' } }),
                    fetch('/api/queue', { cache: 'no-store', headers: { Accept: 'application/json' } }),
                ]);
                if (!barberRes.ok || !queueRes.ok) throw new Error('status refresh failed');
                this.barbers = await barberRes.json();
                this.tickets = await queueRes.json();
                this.failed = false;
            } catch (error) {
                this.failed = true;
            } finally {
                this.loaded = true;
            }
        },
        init() {
            this.refresh();
            const timer = setInterval(() => this.refresh(), 15000);
            window.addEventListener('beforeunload', () => clearInterval(timer), { once: true });
            window.addEventListener('sidecut:barbers-changed', () => this.refresh());
        }
    }"
    class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
    <div class="flex items-center justify-between">
        <span class="text-theme-xs font-medium uppercase tracking-[0.08em] text-gray-400 dark:text-gray-500">Shop status</span>
        <span class="text-theme-xs inline-block whitespace-nowrap rounded-full px-2 py-0.5 font-medium"
            :class="!loaded ? 'bg-gray-100 text-gray-500 dark:bg-gray-500/15 dark:text-gray-400'
                : (failed ? 'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-400'
                : (isOpen ? 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-500' : 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-500'))"
            x-text="!loaded ? 'Checking' : (failed ? 'Offline' : (isOpen ? 'Open' : 'Closed'))">Checking</span>
    </div>

    <dl class="mt-3 grid grid-cols-3 gap-2 text-center">
        <div class="rounded-lg bg-gray-50 py-2 dark:bg-white/[0.03]">
            <dt class="text-[11px] text-gray-500 dark:text-gray-400">Waiting</dt>
            <dd class="mt-0.5 text-base font-semibold tabular-nums text-gray-800 dark:text-white/90" x-text="loaded ? waiting : '–'">–</dd>
        </div>
        <div class="rounded-lg bg-gray-50 py-2 dark:bg-white/[0.03]">
            <dt class="text-[11px] text-gray-500 dark:text-gray-400">In chair</dt>
            <dd class="mt-0.5 text-base font-semibold tabular-nums text-gray-800 dark:text-white/90" x-text="loaded ? serving : '–'">–</dd>
        </div>
        <div class="rounded-lg bg-gray-50 py-2 dark:bg-white/[0.03]">
            <dt class="text-[11px] text-gray-500 dark:text-gray-400">On duty</dt>
            <dd class="mt-0.5 text-base font-semibold tabular-nums text-gray-800 dark:text-white/90">
                <span x-text="loaded ? onDuty : '–'">–</span><span class="text-theme-xs font-normal text-gray-400" x-show="loaded" x-text="'/' + barbers.length"></span>
            </dd>
        </div>
    </dl>

    <a href="/queue#barbers" class="mt-3 inline-flex items-center gap-1 text-theme-xs font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400">
        Manage barbers
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </a>
</div>
