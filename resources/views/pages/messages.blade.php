@extends('layouts.app')

@section('content')
    <x-common.page-header title="Messages"
        description="The texts customers receive from Sidecut, and a log of every send attempt." />

    <div class="space-y-10">
        <x-tables.basic-tables.basic-tables-message-templates />

        <section aria-labelledby="sms-log-heading">
            <h2 id="sms-log-heading" class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Delivery log</h2>
            <x-tables.basic-tables.basic-tables-sms-logs />
        </section>
    </div>
@endsection
