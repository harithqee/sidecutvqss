<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6"
     x-data="{
        chart: null,
        initChart() {
            this.chart = new ApexCharts(this.$refs.chartMonthly, {
                series: [{
                    name: 'Customers Served',
                    data: [180, 210, 195, 240, 260, 230, 275, 300, 285, 250, 220, 190],
                }],
                chart: {
                    type: 'bar',
                    height: 280,
                    fontFamily: 'Outfit, sans-serif',
                    toolbar: { show: false },
                },
                plotOptions: {
                    bar: {
                        borderRadius: 5,
                        columnWidth: '55%',
                        borderRadiusApplication: 'end',
                    },
                },
                colors: ['#465FFF'],
                dataLabels: { enabled: false },
                stroke: {
                    show: true,
                    width: 4,
                    colors: ['transparent'],
                },
                xaxis: {
                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                grid: {
                    borderColor: '#E4E7EC',
                    strokeDashArray: 4,
                    yaxis: { lines: { show: true } },
                },
                fill: { opacity: 1 },
                tooltip: {
                    y: { formatter: (val) => val + ' customers' },
                },
                responsive: [
                    {
                        breakpoint: 640,
                        options: {
                            chart: { height: 220 },
                            plotOptions: { bar: { columnWidth: '70%' } },
                        },
                    },
                ],
            });
            this.chart.render();
        }
     }"
     x-init="initChart()">

    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                Monthly Report
            </h3>
            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                Customers served by month
            </p>
        </div>
        <!-- Dropdown Menu -->
        <x-common.dropdown-menu />
        <!-- End Dropdown Menu -->
    </div>

    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <div x-ref="chartMonthly" class="-ml-5 min-w-[650px] xl:min-w-full"></div>
    </div>
</div>