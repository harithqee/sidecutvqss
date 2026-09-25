<div x-data="{
    chart: null,
    fromDate: null,
    toDate: null,

    formatLocalDate(d) {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    },

    displayLabel() {
        if (!this.fromDate || !this.toDate) return 'Today';
        const from = this.fromDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const to = this.toDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        return from === to ? from : from + ' – ' + to;
    },

    async fetchAndRender() {
        let url = '/api/stats/barber-performance';
        if (this.fromDate && this.toDate) {
            url += '?from=' + this.formatLocalDate(this.fromDate) + '&to=' + this.formatLocalDate(this.toDate);
        }
        const res = await fetch(url);
        const data = await res.json();

        const names = data.map(function(b) { return b.name; });
        const completed = data.map(function(b) { return b.completed; });

        if (this.chart) {
            this.chart.updateOptions({ xaxis: { categories: names } });
            this.chart.updateSeries([{ data: completed }]);
            return;
        }

        this.chart = new ApexCharts(this.$refs.chartBarberPerf, {
            series: [{ name: 'Customers Served', data: completed }],
            chart: {
                type: 'bar',
                height: 250,
                fontFamily: 'Outfit, sans-serif',
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 500,
                    animateGradually: {
                        enabled: true,
                        delay: 100
                    },
                    dynamicAnimation: {
                        enabled: true,
                        speed: 300
                    }
                },
                redrawOnWindowResize: false,
                redrawOnParentResize: false,
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 6,
                    barHeight: '40%',
                },
            },
            colors: ['#039855'],
            dataLabels: { enabled: true },
            xaxis: {
                categories: names,
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            grid: {
                borderColor: '#E4E7EC',
                strokeDashArray: 4,
            },
            responsive: [
                {
                    breakpoint: 640,
                    options: { chart: { height: 200 } },
                },
            ],
        });
        this.chart.render();
    },

    async init() {
        await this.fetchAndRender();
    }
}" x-init="init()"
   @barber-date-changed.window="fromDate = new Date($event.detail.from); toDate = new Date($event.detail.to); fetchAndRender()">

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-5">
            <div>
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    Barber Performance
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Customers served — <span x-text="displayLabel()"></span>
                </p>
            </div>

            <div x-data="{
                init() {
                    flatpickr(this.$refs.barberDatepicker, {
                        mode: 'range',
                        static: true,
                        monthSelectorType: 'static',
                        dateFormat: 'M j',
                        prevArrow: '<svg class=\'stroke-current\' width=\'24\' height=\'24\' viewBox=\'0 0 24 24\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'><path d=\'M15.25 6L9 12.25L15.25 18.5\' stroke=\'\' stroke-width=\'1.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/></svg>',
                        nextArrow: '<svg class=\'stroke-current\' width=\'24\' height=\'24\' viewBox=\'0 0 24 24\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'><path d=\'M8.75 19L15 12.75L8.75 6.5\' stroke=\'\' stroke-width=\'1.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/></svg>',
                        onReady: (selectedDates, dateStr, instance) => {
                            const customClass = instance.element.getAttribute('data-class');
                            if (instance.calendarContainer) {
                                instance.calendarContainer.classList.add(customClass);
                            }
                        },
                        onChange: (selectedDates, dateStr, instance) => {
                            instance.element.value = dateStr.replace('to', '-');
                            if (selectedDates.length === 2) {
                                this.$dispatch('barber-date-changed', { from: selectedDates[0], to: selectedDates[1] });
                            }
                        },
                    })
                }
            }" class="relative max-w-40">
                <input x-ref="barberDatepicker" class="h-10 w-full max-w-11 rounded-lg border border-gray-200 bg-white py-2.5 pl-[34px] pr-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs focus:outline-hidden focus:ring-0 focus-visible:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 xl:max-w-fit xl:pl-11" placeholder="Select dates" data-class="flatpickr-right" readonly="readonly" />
                <div class="absolute inset-0 right-auto flex items-center pointer-events-none left-4">
                    <svg class="fill-gray-700 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.66683 1.54199C7.08104 1.54199 7.41683 1.87778 7.41683 2.29199V3.00033H12.5835V2.29199C12.5835 1.87778 12.9193 1.54199 13.3335 1.54199C13.7477 1.54199 14.0835 1.87778 14.0835 2.29199V3.00033L15.4168 3.00033C16.5214 3.00033 17.4168 3.89576 17.4168 5.00033V7.50033V15.8337C17.4168 16.9382 16.5214 17.8337 15.4168 17.8337H4.5835C3.47893 17.8337 2.5835 16.9382 2.5835 15.8337V7.50033V5.00033C2.5835 3.89576 3.47893 3.00033 4.5835 3.00033L5.91683 3.00033V2.29199C5.91683 1.87778 6.25262 1.54199 6.66683 1.54199ZM6.66683 4.50033H4.5835C4.30735 4.50033 4.0835 4.72418 4.0835 5.00033V6.75033H15.9168V5.00033C15.9168 4.72418 15.693 4.50033 15.4168 4.50033H13.3335H6.66683ZM15.9168 8.25033H4.0835V15.8337C4.0835 16.1098 4.30735 16.3337 4.5835 16.3337H15.4168C15.693 16.3337 15.9168 16.1098 15.9168 15.8337V8.25033Z" fill="" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="p-4 border-t border-gray-100 dark:border-gray-800 sm:p-6">
            <div class="custom-scrollbar max-w-full overflow-x-auto">
                <div x-ref="chartBarberPerf" class="min-w-[600px]"></div>
            </div>
        </div>
    </div>
</div>