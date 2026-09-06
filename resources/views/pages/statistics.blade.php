@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Statistics" />

    <div class="space-y-6">

        <!-- Summary Metric Cards -->
        <x-tables.basic-tables.basic-tables-stats-summary />

        <!-- Queue Volume / Peak Hours / Wait Times Chart -->
        <div class="col-span-12">
            <x-ecommerce.queue-stats-chart />
        </div>

        <!-- Monthly Report -->
        <div class="col-span-12">
            <x-ecommerce.monthly-report />
        </div>

        <!-- Barber Performance -->
        <x-common.component-card title="Barber Performance Today">
            <div class="custom-scrollbar max-w-full overflow-x-auto">
                <div id="chartBarberPerf" class="min-w-[600px]"></div>
            </div>
        </x-common.component-card>

    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    var chartBarberPerf = new ApexCharts(document.querySelector('#chartBarberPerf'), {
        series: [{
            name: 'Customers Served',
            data: [14, 11],
        }],
        chart: {
            type: 'bar',
            height: 250,
            fontFamily: 'Outfit, sans-serif',
            toolbar: { show: false },
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
            categories: ['Server #1', 'Server #2'],
        },
        grid: {
            borderColor: '#E4E7EC',
            strokeDashArray: 4,
        },
        responsive: [
            {
                breakpoint: 640,
                options: {
                    chart: { height: 200 },
                },
            },
        ],
    });
    chartBarberPerf.render();

});
</script>
@endpush