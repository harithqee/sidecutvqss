<div class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
     x-data="{
        currentlyInQueue: 0,
        utilizationPercent: 0,
        queueActive: false,
        isLive: true,
        userPaused: false,
        intervalId: null,
        chart: null,

        get utilizationLabel() {
            if (!this.queueActive) return { text: 'Paused', class: 'bg-gray-100 text-gray-600 dark:bg-gray-500/15 dark:text-gray-300' };
            if (this.utilizationPercent >= 90) return { text: 'Near Full', class: 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-500' };
            if (this.utilizationPercent >= 60) return { text: 'Busy', class: 'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-400' };
            return { text: 'Available', class: 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' };
        },
        get statusMessage() {
            if (!this.queueActive) return 'Queue usage is paused because no barbers are active.';
            if (this.utilizationPercent >= 90) return 'Queue is nearly at capacity. Consider informing walk-ins of longer wait times.';
            if (this.utilizationPercent >= 60) return 'Queue is busy but manageable. Keep an eye on wait times.';
            return 'Queue has room to spare. Great time to welcome walk-ins!';
        },
        get gaugeColor() {
            if (!this.queueActive) return '#98A2B3';
            if (this.utilizationPercent >= 90) return '#D92D20';
            if (this.utilizationPercent >= 60) return '#F79009';
            return '#465FFF';
        },
        // Which band of the scale under the gauge is lit.
        get band() {
            if (!this.queueActive) return null;
            if (this.utilizationPercent >= 90) return 'full';
            if (this.utilizationPercent >= 60) return 'busy';
            return 'available';
        },

        // M/M/S utilization estimate using only today's in_queue/serving tickets
        // and active barbers (see StatisticsController::summary()).
        async fetchQueueData() {
            const barberRes = await fetch('/api/barbers');
            const barbers = await barberRes.json();
            this.queueActive = barbers.some(barber => barber.is_active);
            this.isLive = this.queueActive && !this.userPaused;

            // Always check whether the live queue is empty, even when metric
            // updates are paused, so the gauge cannot remain stuck on an old value.
            const queueRes = await fetch('/api/queue', { cache: 'no-store' });
            const tickets = await queueRes.json();
            this.currentlyInQueue = tickets.length;

            if (!this.queueActive || this.currentlyInQueue === 0) {
                this.utilizationPercent = 0;
                this.updateChart();
                return;
            }

            // Keep the last non-empty metrics when the user pauses live updates.
            if (this.userPaused) return;

            const statsRes = await fetch('/api/stats/summary', { cache: 'no-store' });
            const stats = await statsRes.json();
            this.utilizationPercent = stats.utilization_pct ?? 0;
            this.updateChart();
        },

        toggleLive() {
            if (!this.queueActive) {
                this.isLive = false;
                return;
            }
            this.userPaused = !this.userPaused;
            this.isLive = !this.userPaused;
            if (this.isLive) this.fetchQueueData();
        },

        startPolling() {
            this.intervalId = setInterval(() => {
                this.fetchQueueData();
            }, 10000);
        },

        initChart() {
            // Semicircle only; the value and status are drawn in HTML so they follow the theme.
            this.chart = new ApexCharts(this.$refs.gauge, {
                series: [this.utilizationPercent],
                colors: [this.gaugeColor],
                chart: {
                    fontFamily: 'Outfit, sans-serif',
                    type: 'radialBar',
                    height: 300,
                    sparkline: { enabled: true },
                    animations: { enabled: true, speed: 400 },
                    redrawOnWindowResize: false,
                    redrawOnParentResize: false,
                },
                plotOptions: {
                    radialBar: {
                        startAngle: -90,
                        endAngle: 90,
                        hollow: { size: '74%' },
                        track: { background: 'rgba(152, 162, 179, 0.18)', strokeWidth: '100%', margin: 0 },
                        dataLabels: { show: false },
                    },
                },
                fill: { type: 'solid', colors: [this.gaugeColor] },
                stroke: { lineCap: 'round' },
                labels: ['Utilization'],
            });
            this.chart.render();
        },

        updateChart() {
            if (!this.chart) return;
            this.chart.updateOptions({
                colors: [this.gaugeColor],
                fill: { colors: [this.gaugeColor] },
            });
            this.chart.updateSeries([this.utilizationPercent]);
        },

        async init() {
            this.initChart();
            await this.fetchQueueData();
            this.startPolling();
        }
     }">

    <!-- Header -->
    <div class="flex items-start justify-between gap-3 px-5 pt-5 sm:px-6 sm:pt-6">
        <div>
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg">Queue Usage</h3>
            <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400 sm:text-theme-sm">M/M/S utilization estimate from the active queue and barber capacity.</p>
        </div>
        <button @click="toggleLive()" type="button" :aria-pressed="isLive"
            :title="isLive ? 'Pause live updates' : (queueActive ? 'Resume live updates' : 'No barbers are active')"
            class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-lg bg-white px-2.5 text-theme-xs font-medium text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
            <span class="relative flex h-2 w-2">
                <span x-show="isLive" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full" :class="isLive ? 'bg-success-500' : 'bg-gray-400'"></span>
            </span>
            <span x-text="isLive ? 'Live' : 'Paused'"></span>
        </button>
    </div>

    <!-- Gauge -->
    <div class="flex flex-1 flex-col justify-center px-5 sm:px-6">
        <div class="relative mx-auto mt-4 h-[160px] w-full max-w-[300px] overflow-hidden">
            <div x-ref="gauge"></div>
            <div class="pointer-events-none absolute inset-x-0 bottom-0 flex flex-col items-center">
                <p class="text-title-sm font-semibold leading-none tabular-nums text-gray-900 dark:text-white">
                    <span x-text="utilizationPercent"></span><span class="text-xl text-gray-400">%</span>
                </p>
                <span class="mt-2 rounded-full px-3 py-1 text-theme-xs font-medium transition-colors duration-500"
                    :class="utilizationLabel.class" x-text="utilizationLabel.text"></span>
            </div>
        </div>

        <p class="mx-auto mt-4 max-w-[360px] text-center text-theme-sm text-gray-500 dark:text-gray-400" x-text="statusMessage"></p>

        <!-- What the colours mean -->
        <div class="mx-auto mt-5 grid w-full max-w-[360px] grid-cols-[6fr_3fr_1fr] gap-1" aria-hidden="true">
            <span class="h-1.5 rounded-full transition-colors" :class="band === 'available' ? 'bg-brand-500' : 'bg-gray-200 dark:bg-white/10'"></span>
            <span class="h-1.5 rounded-full transition-colors" :class="band === 'busy' ? 'bg-warning-500' : 'bg-gray-200 dark:bg-white/10'"></span>
            <span class="h-1.5 rounded-full transition-colors" :class="band === 'full' ? 'bg-error-600' : 'bg-gray-200 dark:bg-white/10'"></span>
        </div>
        <div class="mx-auto mt-1.5 grid w-full max-w-[360px] grid-cols-[6fr_3fr_1fr] gap-1 text-[11px] text-gray-400 dark:text-gray-500">
            <span>Available</span>
            <span>Busy 60%+</span>
            <span class="whitespace-nowrap text-right">90%</span>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-5 flex items-center gap-6 border-t border-gray-100 px-5 py-4 dark:border-gray-800 sm:px-6">
        <div>
            <p class="text-theme-xs text-gray-500 dark:text-gray-400">In Queue Now</p>
            <p class="mt-0.5 flex items-center gap-1 text-base font-semibold tabular-nums text-gray-800 dark:text-white/90">
                <span x-text="currentlyInQueue"></span>
                <svg x-show="utilizationPercent > 60" width="14" height="14" viewBox="0 0 16 16" fill="none" class="shrink-0 text-error-600 dark:text-error-400" aria-hidden="true">
                    <path d="M8 2.5v11M3.5 9 8 13.5 12.5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <svg x-show="utilizationPercent <= 60" width="14" height="14" viewBox="0 0 16 16" fill="none" class="shrink-0 text-success-600 dark:text-success-500" aria-hidden="true">
                    <path d="M8 13.5v-11M3.5 7 8 2.5 12.5 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </p>
        </div>
        <div class="h-8 w-px bg-gray-200 dark:bg-gray-800"></div>
        <div>
            <p class="text-theme-xs text-gray-500 dark:text-gray-400">Utilization</p>
            <p class="mt-0.5 text-base font-semibold tabular-nums text-gray-800 dark:text-white/90" x-text="utilizationPercent + '%'"></p>
        </div>
    </div>
</div>
