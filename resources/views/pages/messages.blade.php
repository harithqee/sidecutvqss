@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Messages" />

    <div class="space-y-6">
        <x-tables.basic-tables.basic-tables-message-templates />

        <div>
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">SMS Logs</h2>
            <x-tables.basic-tables.basic-tables-sms-logs />
        </div>
    </div>
@endsection