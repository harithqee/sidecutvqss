<div class="col-span-12 xl:col-span-6">
<div class="rounded-2xl border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-white/[0.03]"
     x-data="{
        maxCapacity: 15,
        currentlyInQueue: 8,
        isLive: true,
        intervalId: null,
        chart: null,

        get utilizationPercent() {
            return Math.round((this.currentlyInQueue / this.maxCapacity) * 100);
        },
        get utilizationLabel() {
            if (this.utilizationPercent >= 90) return { text: 'Near Full', class: 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-500' };
            if (this.utilizationPercent >= 60) return { text: 'Busy', class: 'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-400' };
            return { text: 'Available', class: 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' };
        },
        get statusMessage() {
            if (this.utilizationPercent >= 90) return 'Queue is nearly at capacity. Consider informing walk-ins of longer wait times.';
            if (this.utilizationPercent >= 60) return 'Queue is busy but manageable. Keep an eye on wait times.';
            return 'Queue has room to spare. Great time to welcome walk-ins!';
        },
        get gaugeColor() {
            if (this.utilizationPercent >= 90) return '#D92D20';
            if (this.utilizationPercent >= 60) return '#F79009';
            return '#465FFF';
        },

        simulateQueue() {
            const move = Math.floor(Math.random() * 5) - 2;
            let next = this.currentlyInQueue + move;
            next = Math.max(0, Math.min(this.maxCapacity, next));
            this.currentlyInQueue = next;
            this.updateChart();
        },

        startSimulation() {
            this.intervalId = setInterval(() => {
                if (this.isLive) this.simulateQueue();
            }, 3000);
        },

        toggleLive() {
            this.isLive = !this.isLive;
        },

        initChart() {
            const options = {
                series: [this.utilizationPercent],
                colors: [this.gaugeColor],
                chart: {
                    fontFamily: 'Outfit, sans-serif',
                    type: 'radialBar',
                    height: 330,
                    sparkline: { enabled: true },
                },
                plotOptions: {
                    radialBar: {
                        startAngle: -85,
                        endAngle: 85,
                        hollow: { size: '80%' },
                        track: {
                            background: '#E4E7EC',
                            strokeWidth: '100%',
                            margin: 5,
                        },
                        dataLabels: {
                            name: { show: false },
                            value: {
                                fontSize: '36px',
                                fontWeight: '600',
                                offsetY: -40,
                                color: '#1D2939',
                                formatter: (val) => val + '%',
                            },
                        },
                    },
                },
                fill: { type: 'solid', colors: [this.gaugeColor] },
                stroke: { lineCap: 'round' },
                labels: ['Utilization'],
                responsive: [
                    {
                        breakpoint: 640,
                        options: {
                            chart: { height: 260 },
                            plotOptions: {
                                radialBar: {
                                    dataLabels: {
                                        value: { fontSize: '24px', offsetY: -26 },
                                    },
                                },
                            },
                        },
                    },
                ],
            };

            this.chart = new ApexCharts(this.$refs.chartTwo, options);
            this.chart.render();
        },

        updateChart() {
            if (!this.chart) return;
            this.chart.updateOptions({
                colors: [this.gaugeColor],
                fill: { colors: [this.gaugeColor] },
            });
            this.chart.updateSeries([this.utilizationPercent]);
        }
     }"
     x-init="initChart(); startSimulation();">

    <div class="shadow-default rounded-2xl bg-white px-4 pb-8 pt-5 dark:bg-gray-900 sm:px-6 sm:pb-11 sm:pt-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg">
                    Queue Usage
                </h3>
                <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400 sm:text-theme-sm">
                    How much of your queue capacity is currently in use.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button
                    @click="toggleLive()"
                    type="button"
                    class="flex items-center gap-1.5 rounded-full border border-gray-200 px-2.5 py-1 text-theme-xs font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">
                    <span class="relative flex h-2 w-2">
                        <span x-show="isLive" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full" :class="isLive ? 'bg-success-500' : 'bg-gray-400'"></span>
                    </span>
                    <span x-text="isLive ? 'Live' : 'Paused'"></span>
                </button>

                <x-common.dropdown-menu />
            </div>
        </div>

        <div class="relative mt-4 h-[160px] w-full sm:h-[195px]">
            <div x-ref="chartTwo" class="h-full w-full [&>div]:!h-full [&_svg]:!h-full"></div>
            <span
                class="absolute left-1/2 top-[85%] -translate-x-1/2 -translate-y-[85%] whitespace-nowrap rounded-full px-3 py-1 text-xs font-medium transition-colors duration-500"
                :class="utilizationLabel.class"
                x-text="utilizationLabel.text">
            </span>
        </div>

        <p class="mx-auto mt-1.5 w-full max-w-[380px] text-center text-theme-xs text-gray-500 transition-opacity duration-300 sm:text-sm" x-text="statusMessage">
        </p>
    </div>

    <div class="flex flex-wrap items-center justify-center gap-4 px-4 py-3.5 sm:gap-8 sm:px-6 sm:py-5">

        <div class="min-w-[70px]">
            <p class="mb-1 text-center text-theme-xs text-gray-500 dark:text-gray-400 sm:text-sm">
                Max Capacity
            </p>
            <p class="flex items-center justify-center gap-1 text-sm font-semibold text-gray-800 dark:text-white/90 sm:text-lg">
                <span x-text="maxCapacity"></span>
            </p>
        </div>

        <div class="h-7 w-px bg-gray-200 dark:bg-gray-800"></div>

        <div class="min-w-[70px]">
            <p class="mb-1 text-center text-theme-xs text-gray-500 dark:text-gray-400 sm:text-sm">
                In Queue Now
            </p>
            <p class="flex items-center justify-center gap-1 text-sm font-semibold text-gray-800 dark:text-white/90 sm:text-lg">
                <span x-text="currentlyInQueue" class="transition-all duration-300"></span>
            </p>
        </div>

        <div class="h-7 w-px bg-gray-200 dark:bg-gray-800"></div>

        <div class="min-w-[70px]">
            <p class="mb-1 text-center text-theme-xs text-gray-500 dark:text-gray-400 sm:text-sm">
                Utilization
            </p>
            <p class="flex items-center justify-center gap-1 text-sm font-semibold text-gray-800 dark:text-white/90 sm:text-lg">
                <span x-text="utilizationPercent + '%'" class="transition-all duration-300"></span>
            </p>
        </div>

    </div>
</div>
</div>
