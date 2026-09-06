@extends('layouts.app')

@section('content')
  <div class="grid grid-cols-12 gap-4 md:gap-6">

    <!-- Row 1: Existing ecommerce metrics + monthly target -->
    <div class="col-span-12 space-y-6 xl:col-span-6">
      <x-ecommerce.ecommerce-metrics />
    </div>
    <div class="col-span-12 xl:col-span-6">
        <x-ecommerce.monthly-target />
    </div>

    <!-- Row 2: Queue Status + Server Status (already built as one col-span-6 unit) -->

   

    <!-- Row 3: Queue Stats chart (wide) -->
    <div class="col-span-12">
        <x-ecommerce.queue-stats-chart />
    </div>

    <!-- Row 4: Monthly Report -->
    <div class="col-span-12 xl:col-span-6">
        <x-ecommerce.monthly-report />
    </div>

   

  </div>
@endsection