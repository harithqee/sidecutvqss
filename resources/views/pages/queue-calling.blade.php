@extends('layouts.queue-board')

@section('content')
    <main x-data="queueCallingBoard()" x-init="init()" class="flex h-full flex-col gap-4 p-4 sm:gap-6 sm:p-6 lg:p-8">

        <!-- Top bar -->
        <header class="flex shrink-0 items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <img src="/images/logo/logo-icon.svg" width="40" height="40" alt="" />
                <div class="leading-tight">
                    <p class="text-lg font-semibold text-gray-900 dark:text-white sm:text-xl">Sidecut</p>
                    <p class="text-theme-xs text-gray-500 dark:text-gray-400 sm:text-theme-sm">Barbershop queue</p>
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <span class="hidden items-center gap-2 text-theme-sm text-gray-500 dark:text-gray-400 md:inline-flex">
                    <span class="h-2 w-2 rounded-full"
                        :class="(loading ? 'animate-pulse bg-gray-400' : (connectionError ? 'bg-error-500' : (queueActive ? 'bg-success-500' : 'bg-warning-500')))"></span>
                    <span x-text="loading ? 'Connecting' : (connectionError ? 'Reconnecting' : (queueActive ? 'Live' : 'Paused'))"></span>
                </span>
                <button type="button" @click="toggleAnnouncements()" :aria-pressed="announcementsEnabled"
                    :aria-label="announcementsEnabled ? 'Turn spoken announcements off' : 'Turn spoken announcements on'"
                    :title="announcementsEnabled ? 'Announcements on' : 'Announcements off'"
                    class="flex h-10 w-10 items-center justify-center rounded-lg border transition"
                    :class="announcementsEnabled
                        ? 'border-brand-200 bg-brand-50 text-brand-600 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300'
                        : 'border-gray-200 bg-white text-gray-500 hover:text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:text-white'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M11 5 6 9H3v6h3l5 4V5Z"/>
                        <path x-show="announcementsEnabled" d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/>
                        <path x-show="!announcementsEnabled" d="m16 9 6 6M22 9l-6 6"/>
                    </svg>
                </button>
                <button type="button" @click="toggleFullscreen()" aria-label="Toggle full screen" title="Full screen"
                    class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:text-white">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path x-show="!isFullscreen" d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>
                        <path x-show="isFullscreen" d="M9 4v5H4M15 4v5h5M9 20v-5H4M15 20v-5h5"/>
                    </svg>
                </button>
                <p class="ml-1 font-semibold tabular-nums text-gray-900 dark:text-white sm:ml-3 sm:text-3xl" x-text="clock"></p>
            </div>
        </header>

        <!-- Board -->
        <div class="grid min-h-0 flex-1 grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-[minmax(0,1.65fr)_minmax(0,1fr)]">

            <!-- Now calling -->
            <section class="relative flex min-h-[320px] flex-col overflow-hidden rounded-3xl p-6 transition-colors duration-500 sm:p-10"
                :class="newCall ? 'bg-brand-500 text-white' : 'bg-white text-gray-900 shadow-theme-sm dark:bg-gray-900 dark:text-white'"
                aria-live="assertive">
                <p class="text-theme-sm font-semibold uppercase tracking-[0.2em] sm:text-base"
                    :class="newCall ? 'text-white/80' : 'text-brand-500 dark:text-brand-400'"
                    x-text="queueActive ? 'Now calling' : 'Queue paused'">Now calling</p>

                <div class="flex flex-1 flex-col justify-center">
                    <template x-if="queueActive && currentCall">
                        <div>
                            <p class="call-number font-semibold leading-[0.85] tracking-tight tabular-nums" :class="newCall ? 'call-flash' : ''">
                                <span :class="newCall ? 'text-white/50' : 'text-gray-300 dark:text-gray-700'">#</span><span x-text="pad(currentCall.queue_number)"></span>
                            </p>
                            <p class="mt-6 text-[clamp(1.5rem,3.6vw,3.25rem)] font-medium leading-tight">
                                <span :class="newCall ? 'text-white/80' : 'text-gray-500 dark:text-gray-400'">Please go to</span>
                                <span class="font-semibold" x-text="currentCall.barber || 'the counter'"></span>
                            </p>
                        </div>
                    </template>

                    <template x-if="queueActive && !currentCall">
                        <div>
                            <p class="call-number font-semibold leading-[0.85] tracking-tight text-gray-200 dark:text-gray-800">—</p>
                            <p class="mt-6 text-[clamp(1.25rem,2.6vw,2.25rem)] text-gray-500 dark:text-gray-400">Waiting for the next call</p>
                        </div>
                    </template>

                    <template x-if="!queueActive">
                        <div>
                            <p class="text-[clamp(2.5rem,7vw,6rem)] font-semibold leading-none tracking-tight">We're not taking customers right now</p>
                            <p class="mt-5 max-w-xl text-[clamp(1rem,1.8vw,1.5rem)] text-gray-500 dark:text-gray-400">No barbers are on duty. The board comes back on by itself when the queue opens.</p>
                        </div>
                    </template>
                </div>
            </section>

            <!-- Side column -->
            <div class="flex min-h-0 flex-col gap-4 sm:gap-6">
                <section class="flex min-h-0 flex-1 flex-col rounded-3xl bg-white p-6 shadow-theme-sm dark:bg-gray-900 sm:p-8">
                    <div class="flex items-baseline justify-between">
                        <h2 class="text-theme-sm font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 sm:text-base">Up next</h2>
                        <p class="text-theme-sm text-gray-500 dark:text-gray-400 sm:text-base" x-show="queueActive">
                            <span class="font-semibold tabular-nums text-gray-900 dark:text-white" x-text="waitingCount"></span> waiting
                        </p>
                    </div>

                    <ol class="mt-4 min-h-0 flex-1 divide-y divide-gray-100 overflow-hidden dark:divide-gray-800">
                        <template x-for="ticket in upNext" :key="ticket.id">
                            <li class="flex items-center justify-between gap-4 py-3 sm:py-4">
                                <span class="text-[clamp(1.75rem,3.2vw,3rem)] font-semibold leading-none tabular-nums text-gray-900 dark:text-white">
                                    <span class="text-gray-300 dark:text-gray-700">#</span><span x-text="pad(ticket.queue_number)"></span>
                                </span>
                                <span class="truncate text-right text-[clamp(1rem,1.5vw,1.375rem)] text-gray-500 dark:text-gray-400" x-text="ticket.barber || 'Any barber'"></span>
                            </li>
                        </template>
                    </ol>
                    <p x-show="queueActive && upNext.length === 0" class="flex flex-1 items-center text-lg text-gray-400 dark:text-gray-500">Nobody else in line.</p>
                    <p x-show="hiddenCount > 0" class="pt-3 text-theme-sm text-gray-500 dark:text-gray-400" x-text="'+ ' + hiddenCount + ' more'"></p>
                </section>

                <section class="shrink-0 rounded-3xl bg-white p-6 shadow-theme-sm dark:bg-gray-900 sm:p-8" x-show="queueActive">
                    <h2 class="text-theme-sm font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 sm:text-base">Called recently</h2>
                    <ul class="mt-4 grid grid-cols-3 gap-3">
                        <template x-for="(ticket, index) in recentSlots" :key="ticket ? ticket.id : 'empty-' + index">
                            <li class="rounded-2xl bg-gray-50 px-3 py-4 text-center dark:bg-white/[0.04]">
                                <template x-if="ticket">
                                    <div>
                                        <p class="text-[clamp(1.25rem,2.2vw,2rem)] font-semibold leading-none tabular-nums text-gray-700 dark:text-gray-200" x-text="'#' + pad(ticket.queue_number)"></p>
                                        <p class="mt-2 truncate text-theme-xs text-gray-500 dark:text-gray-400 sm:text-theme-sm" x-text="ticket.barber || 'Counter'"></p>
                                    </div>
                                </template>
                                <template x-if="!ticket">
                                    <p class="text-[clamp(1.25rem,2.2vw,2rem)] font-semibold leading-none text-gray-200 dark:text-gray-800">—</p>
                                </template>
                            </li>
                        </template>
                    </ul>
                </section>
            </div>
        </div>

        <!-- Footer -->
        <footer class="flex shrink-0 flex-wrap items-center justify-between gap-2 text-theme-sm text-gray-500 dark:text-gray-400 sm:text-base">
            <p>Join the queue from your phone: <span class="font-semibold text-gray-900 dark:text-white">{{ request()->getHttpHost() }}/customers</span></p>
            <p class="text-theme-xs tabular-nums sm:text-theme-sm" x-show="lastUpdated">Updated <span x-text="lastUpdated"></span></p>
        </footer>
    </main>

    <style>
        .call-number { font-size: clamp(6rem, min(22vw, 34vh), 20rem); }
        @keyframes call-flash {
            0%, 100% { opacity: 1; }
            50% { opacity: .55; }
        }
        .call-flash { animation: call-flash .8s ease-in-out 4; }
        @media (prefers-reduced-motion: reduce) {
            .call-flash { animation: none; }
        }
    </style>

    <script>
        function queueCallingBoard() {
            return {
                currentCall: null, recentCalls: [], upcoming: [], waitingCount: 0, queueActive: true, loading: true,
                connectionError: false, lastUpdated: '', previousCallId: null, previousCallVersion: null,
                newCall: false, newCallTimer: null, announcementsEnabled: false, pollTimer: null,
                clock: '', isFullscreen: false,
                get upNext() { return this.upcoming.slice(0, 5); },
                get hiddenCount() { return Math.max(0, this.waitingCount - (this.currentCall ? 1 : 0) - this.upNext.length); },
                get recentSlots() {
                    return [...this.recentCalls.slice(0, 3), ...Array(Math.max(0, 3 - this.recentCalls.length)).fill(null)];
                },
                async init() {
                    this.announcementsEnabled = localStorage.getItem('calling-board-sound') === 'on';
                    this.tick();
                    window.setInterval(() => this.tick(), 1000);
                    document.addEventListener('fullscreenchange', () => { this.isFullscreen = !!document.fullscreenElement; });
                    await this.refresh(false);
                    this.pollTimer = window.setInterval(() => this.refresh(true), 3000);
                    window.addEventListener('beforeunload', () => window.clearInterval(this.pollTimer), { once: true });
                },
                tick() {
                    this.clock = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                },
                async refresh(announceNew) {
                    try {
                        const response = await fetch('/api/queue/calling-board', { cache: 'no-store', headers: { Accept: 'application/json' } });
                        if (!response.ok) throw new Error('Unable to load the calling board.');
                        const data = await response.json();
                        this.queueActive = data.queue_active !== false;
                        const nextCall = data.current_call || null;
                        const isNewCall = announceNew && this.queueActive && nextCall &&
                            (nextCall.id !== this.previousCallId || nextCall.call_version !== this.previousCallVersion);
                        this.currentCall = nextCall;
                        this.recentCalls = data.recent_calls || [];
                        // The person being called is shown big on the left; don't repeat them in "Up next".
                        this.upcoming = (data.upcoming || []).filter(ticket => !nextCall || ticket.id !== nextCall.id);
                        this.waitingCount = data.waiting_count || 0;
                        if (isNewCall) {
                            this.newCall = true;
                            window.clearTimeout(this.newCallTimer);
                            this.newCallTimer = window.setTimeout(() => { this.newCall = false; }, 6000);
                            if (this.announcementsEnabled) this.announce(nextCall);
                        }
                        this.previousCallId = nextCall ? nextCall.id : null;
                        this.previousCallVersion = nextCall ? nextCall.call_version : null;
                        this.lastUpdated = new Date(data.updated_at || Date.now()).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        this.connectionError = false;
                    } catch (error) {
                        this.connectionError = true;
                    } finally {
                        this.loading = false;
                    }
                },
                pad(number) { return String(number).padStart(3, '0'); },
                counterLabel(ticket) { return ticket.barber ? ticket.barber : 'the counter'; },
                toggleAnnouncements() {
                    this.announcementsEnabled = !this.announcementsEnabled;
                    localStorage.setItem('calling-board-sound', this.announcementsEnabled ? 'on' : 'off');
                    if ('speechSynthesis' in window) window.speechSynthesis.cancel();
                },
                toggleFullscreen() {
                    if (document.fullscreenElement) document.exitFullscreen();
                    else document.documentElement.requestFullscreen?.();
                },
                announce(ticket) {
                    if (!('speechSynthesis' in window)) return;
                    window.speechSynthesis.cancel();
                    window.speechSynthesis.speak(new SpeechSynthesisUtterance(`Number ${ticket.queue_number}, please go to ${this.counterLabel(ticket)}.`));
                }
            };
        }
    </script>
@endsection
