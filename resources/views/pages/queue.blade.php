@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Queue" />

    <div class="space-y-6">

        <div>
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">
                Manage Servers
            </h2>
            <x-tables.basic-tables.tables-manage-servers/>
        </div>

        <x-tables.basic-tables.basic-tables-queue />

        <div>
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">
                Queue History
            </h2>
            <x-tables.basic-tables.basic-tables-queue-history />
        </div>

    </div>
@endsection