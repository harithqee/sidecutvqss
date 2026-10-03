@extends('layouts.app')

@section('content')
    <div x-data="{
            tab: 'live',
            tabs: ['live', 'barbers', 'history'],
            init() {
                const fromHash = () => {
                    const hash = window.location.hash.replace('#', '');
                    if (this.tabs.includes(hash)) this.tab = hash;
                };
                fromHash();
                window.addEventListener('hashchange', fromHash);
            },
            show(name) {
                this.tab = name;
                history.replaceState(null, '', '#' + name);
            }
        }">
        <x-common.page-header title="Manage Queue"
            description="Everyone in today's line, the barbers taking customers, and who has been served." />

        <div class="mb-6 border-b border-gray-200 dark:border-gray-800">
            <nav class="-mb-px flex gap-6 overflow-x-auto no-scrollbar" role="tablist" aria-label="Queue sections">
                @foreach (['live' => 'Live queue', 'barbers' => 'Barbers', 'history' => 'History'] as $key => $label)
                    <button type="button" role="tab" @click="show('{{ $key }}')" :aria-selected="tab === '{{ $key }}'"
                        class="whitespace-nowrap border-b-2 pb-3 text-theme-sm font-medium transition"
                        :class="tab === '{{ $key }}'
                            ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                            : 'border-transparent text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        <div x-show="tab === 'live'" role="tabpanel">
            <x-tables.basic-tables.basic-tables-queue />
        </div>
        <div x-show="tab === 'barbers'" x-cloak role="tabpanel">
            <x-tables.basic-tables.tables-manage-servers />
        </div>
        <div x-show="tab === 'history'" x-cloak role="tabpanel">
            <x-tables.basic-tables.basic-tables-queue-history />
        </div>
    </div>
@endsection
