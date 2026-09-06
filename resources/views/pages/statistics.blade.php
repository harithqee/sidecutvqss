@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Statistics" />

    <div class="space-y-6">

        <!-- Summary Metric Cards -->
        <x-tables.basic-tables.basic-tables-stats-summary />

        <!-- Queue Volume Line Chart -->
        <x-common.component-card title="Queue Volume Over Time">
            <div class="custom-scrollbar max-w-full overflow-x-auto">
                <div id="chartEight" class="min-w-[600px]"></div>
            </div>
        </x-common.component-card>

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

    // Queue Volume — customers per hour
    var chartEight = new ApexCharts(document.querySelector('#chartEight'), {
        series: [{
            name: 'Customers',
            data: [2, 5, 8, 12, 15, 10, 18, 22, 19, 14, 9, 4],
        }],
        chart: {
            type: 'area',
            height: 310,
            fontFamily: 'Outfit, sans-serif',
            toolbar: { show: false },
        },
        colors: ['#465FFF'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0,
                stops: [0, 90, 100],
            },
        },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        grid: {
            borderColor: '#E4E7EC',
            strokeDashArray: 4,
        },
        xaxis: {
            categories: ['9AM', '10AM', '11AM', '12PM', '1PM', '2PM', '3PM', '4PM', '5PM', '6PM', '7PM', '8PM'],
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            title: { text: undefined },
        },
        tooltip: {
            y: { formatter: function (val) { return val + ' customers'; } },
        },
        responsive: [
            {
                breakpoint: 640,
                options: {
                    chart: { height: 240 },
                },
            },
        ],
    });
    chartEight.render();

    // Barber Performance — customers served today per barber
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