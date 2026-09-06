@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Messages" />

    <div class="space-y-6">
        <x-tables.basic-tables.basic-tables-message-templates />
    </div>
@endsection