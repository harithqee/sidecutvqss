<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6"
     x-data="{
        chart: null,

        async initChart() {
            const res = await fetch('/api/stats/monthly-report');
            const data = await res.json();
            const monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const totals = new Array(12).fill(0);
            data.forEach(function(row) {
                totals[row.month - 1] = row.total;
            });

            this.chart = new ApexCharts(this.$refs.chartMonthly, {
                series: [{ name: 'Customers Served', data: totals }],
                chart: {
                    type: 'bar',
                    height: 280,
                    fontFamily: 'Outfit, sans-serif',
                    toolbar: { show: false },
                },
                plotOptions: {
                    bar: { borderRadius: 5, columnWidth: '55%', borderRadiusApplication: 'end' },
                },
                colors: ['#465FFF'],
                dataLabels: { enabled: false },
                stroke: { show: true, width: 4, colors: ['transparent'] },
                xaxis: {
                    categories: monthLabels,
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                grid: { borderColor: '#E4E7EC', strokeDashArray: 4, yaxis: { lines: { show: true } } },
                fill: { opacity: 1 },
                tooltip: { y: { formatter: (val) => val + ' customers' } },
                responsive: [{ breakpoint: 640, options: { chart: { height: 220 }, plotOptions: { bar: { columnWidth: '70%' } } } }],
            });
            this.chart.render();
        }
     }"
     x-init="initChart()">

    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Monthly Report</h3>
            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Customers served by month</p>
        </div>
        <x-common.dropdown-menu />
    </div>

    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <div x-ref="chartMonthly" class="-ml-5 min-w-[650px] xl:min-w-full"></div>
    </div>
</div>