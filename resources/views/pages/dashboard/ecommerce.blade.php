@extends('layouts.app')

@section('content')
  <div class="grid grid-cols-12 gap-4 md:gap-6">
    
    <div class="col-span-12">
      <x-tables.basic-tables.basic-tables-stats-summary />
    </div>

    <!-- Row 1: Server Status + Queue Usage -->
    <div class="col-span-12 xl:col-span-6">
      <x-ecommerce.queue-server-status />
    </div>

    <div class="col-span-12 xl:col-span-6">
      <x-ecommerce.queue-usage />
    </div>

    <!-- Row 2: Queue Stats chart (wide) -->
    <div class="col-span-12">
      <x-ecommerce.queue-stats-chart />
    </div>


  </div>
@endsection