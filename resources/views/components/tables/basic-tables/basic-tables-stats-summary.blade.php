<div
     x-data="{
        stats: [],
        queueStats: [],

        async init() {
            const res = await fetch('/api/stats/summary');
            const d = await res.json();

            this.stats = [
                { label: 'Customers Today', value: String(d.customers_today) },
                { label: 'Avg Wait Time', value: d.avg_wait_minutes + ' min' },
                { label: 'Avg Service Time', value: d.avg_service_minutes + ' min' },
                { label: 'Completion Rate', value: d.completion_rate + '%' },
            ];

            // Queueing-theory (M/M/S) cards, derived server-side from today's session.
            this.queueStats = [
                {
                    label: 'System Utilization (ρ)',
                    value: d.utilization_pct !== null ? d.utilization_pct + '%' : '—',
                    hint: d.servers_active + ' barber(s) active',
                    trend: (d.utilization_pct !== null && d.utilization_pct >= 85) ? 'down' : 'up',
                },
                {
                    label: 'Predicted Wait (Model)',
                    value: d.predicted_wait_minutes !== null ? d.predicted_wait_minutes + ' min' : '—',
                    hint: 'M/M/S estimate, vs ' + d.avg_wait_minutes + ' min actual',
                    trend: (d.predicted_wait_minutes !== null && d.predicted_wait_minutes <= d.avg_wait_minutes) ? 'up' : 'down',
                },
                {
                    label: 'Avg in Queue (Lq)',
                    value: d.avg_in_queue !== null ? d.avg_in_queue : '—',
                    hint: 'Customers waiting, on average',
                    trend: 'up',
                },
                {
                    label: 'Avg in System (L)',
                    value: d.avg_in_system !== null ? d.avg_in_system : '—',
                    hint: 'Waiting + being served',
                    trend: 'up',
                },
            ];

            if (d.queue_model_stable === false) {
                console.warn('Queueing model unstable: arrivals currently exceed total barber capacity (ρ ≥ 1).');
            }
        }
     }">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
        <template x-for="stat in stats" :key="stat.label">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
                <span class="text-sm text-gray-500 dark:text-gray-400" x-text="stat.label"></span>
                <div class="mt-2 flex items-end justify-between">
                    <h4 class="font-bold text-gray-800 text-title-sm dark:text-white/90" x-text="stat.value"></h4>
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-500/15 dark:text-gray-300">Today</span>
                </div>
            </div>
        </template>
    </div>

    <!-- Queueing-theory model cards (M/M/S), derived from today's queue history -->
    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4 md:mt-6">
        <template x-for="stat in queueStats" :key="stat.label">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
                <span class="text-sm text-gray-500 dark:text-gray-400" x-text="stat.label"></span>
                <div class="mt-2 flex items-end justify-between">
                    <h4 class="font-bold text-gray-800 text-title-sm dark:text-white/90" x-text="stat.value"></h4>
                    <span class="flex items-center gap-1 rounded-full py-0.5 pl-2 pr-2.5 text-xs font-medium"
                          :class="stat.trend === 'up' ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' : 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-500'"
                          x-text="stat.trend === 'up' ? 'Healthy' : 'Watch'"></span>
                </div>
                <p class="mt-1 text-theme-xs text-gray-400 dark:text-gray-500" x-text="stat.hint"></p>
            </div>
        </template>
    </div>
</div>
