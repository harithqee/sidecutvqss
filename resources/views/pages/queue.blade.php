@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Queue" />
    
    <div class="space-y-6">
            <x-tables.basic-tables.basic-tables-queue />
     </div>
@endsection
