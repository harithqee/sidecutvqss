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
        <x-ecommerce.barber-performance />

    </div>
@endsection