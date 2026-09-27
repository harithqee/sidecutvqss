@extends('layouts.queue-board')

@section('content')
    <main x-data="queueCallingBoard()" x-init="init()" class="relative flex min-h-screen flex-col items-center justify-between overflow-hidden px-4 py-5 sm:px-8 sm:py-7">
        <header class="relative z-10 flex w-full max-w-7xl items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <img src="/images/logo/logo-icon.svg" width="34" height="34" alt="SidecutVQS" />
                <div>
                    <p class="text-sm font-bold tracking-wide text-gray-800 dark:text-white/90 sm:text-base">SidecutVQS</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Customer calling board</p>
                </div>
            </div>
            <div class="flex items-center gap-3 sm:gap-5">
                <button type="button" @click="toggleAnnouncements()" :aria-pressed="announcementsEnabled"
                    class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 sm:text-sm">
                    <span x-text="announcementsEnabled ? '🔊 Sound on' : '🔇 Sound off'">🔇 Sound off</span>
                </button>
                <span class="inline-flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300 sm:text-sm">
                    <span class="h-2.5 w-2.5 rounded-full" :class="(loading ? 'animate-pulse ' : '') + (connectionError ? 'bg-red-400' : (queueActive ? 'bg-success-500' : 'bg-warning-500'))"></span>
                    <span x-text="loading ? 'Connecting' : (connectionError ? 'Reconnecting' : (queueActive ? 'Live' : 'Queue paused'))">Live</span>
                </span>
            </div>
        </header>

        <section class="relative z-10 flex w-full flex-1 flex-col items-center justify-center py-5 sm:py-7">
            <p class="mb-3 text-center text-xs font-bold uppercase tracking-[0.28em] text-brand-600 dark:text-brand-400 sm:mb-5 sm:text-sm" x-text="queueActive ? 'Now calling' : 'Queue status'">Now calling</p>
            <div class="current-call-tile flex aspect-square w-[min(50vh,88vw)] items-center justify-center rounded-2xl border border-gray-200 bg-white shadow-theme-md dark:border-gray-800 dark:bg-white/[0.03] sm:rounded-2xl"
                :class="newCall ? 'current-call-pulse' : ''">
                <template x-if="!queueActive">
                    <div class="px-5 text-center">
                        <p class="text-4xl font-black text-warning-600 dark:text-warning-400 sm:text-6xl">Queue paused</p>
                        <p class="mt-4 max-w-sm text-sm leading-relaxed text-gray-500 dark:text-gray-400 sm:text-lg">No barbers are active. The queue board will resume when a barber becomes available.</p>
                    </div>
                </template>
                <template x-if="queueActive && currentCall">
                    <div class="text-center">
                        <p class="text-[clamp(1rem,3vh,1.75rem)] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Ticket number</p>
                        <p class="mt-3 text-[clamp(5rem,22vh,13rem)] font-black leading-none tracking-tight text-brand-600 dark:text-brand-400" x-text="ticketLabel(currentCall.queue_number)"></p>
                        <p class="mt-4 text-[clamp(1rem,3.5vh,2rem)] font-semibold text-gray-800 dark:text-white/90" x-text="counterLabel(currentCall)"></p>
                    </div>
                </template>
                <template x-if="queueActive && !currentCall">
                    <div class="px-4 text-center">
                        <p class="text-[clamp(5rem,22vh,13rem)] font-black leading-none text-gray-300 dark:text-gray-700">—</p>
                        <p class="mt-5 text-lg font-semibold text-gray-500 dark:text-gray-400 sm:text-2xl">Waiting for the next call</p>
                    </div>
                </template>
            </div>

            <div x-show="queueActive" class="mt-6 w-full sm:mt-8" style="max-width: min(42rem, 72vh)">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 sm:text-sm">Recently called</h2>
                    <span class="text-xs text-gray-500 dark:text-gray-400" x-text="waitingCount + ' waiting'">0 waiting</span>
                </div>
                <div class="grid grid-cols-3 gap-3 sm:gap-5">
                    <template x-for="(ticket, index) in recentSlots" :key="ticket ? ticket.id : `empty-${index}`">
                        <div class="recent-call-tile flex aspect-square flex-col items-center justify-center rounded-2xl border border-gray-200 bg-white px-2 text-center shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                            <template x-if="ticket">
                                <div>
                                    <p class="text-[clamp(.65rem,1.5vh,.9rem)] font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400" x-text="counterLabel(ticket)"></p>
                                    <p class="mt-2 text-[clamp(1.8rem,8vh,4.5rem)] font-extrabold leading-none text-gray-800 dark:text-white/90" x-text="ticketLabel(ticket.queue_number)"></p>
                                </div>
                            </template>
                            <template x-if="!ticket">
                                <p class="text-3xl font-light text-gray-300 dark:text-gray-700">—</p>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <footer class="relative z-10 flex w-full max-w-7xl items-center justify-between text-[11px] text-gray-500 dark:text-gray-400 sm:text-xs">
            <span>Thank you for waiting</span>
            <span>Updated <span x-text="lastUpdated || '—'">—</span></span>
        </footer>
    </main>

    <style>
        @keyframes current-call-pulse {
            0%, 100% { box-shadow: 0 12px 32px rgba(16,24,40,.10), 0 0 0 0 rgba(70,95,255,.22); }
            50% { box-shadow: 0 12px 32px rgba(16,24,40,.10), 0 0 0 16px rgba(70,95,255,0); }
        }
        .current-call-pulse { animation: current-call-pulse .9s ease-in-out 4; }
        @media (max-height: 650px) {
            .current-call-tile { width: min(36vh, 72vw); }
        }
    </style>

    <script>
        function queueCallingBoard() {
            return {
                currentCall: null, recentCalls: [], waitingCount: 0, queueActive: true, loading: true,
                connectionError: false, lastUpdated: '', previousCallId: null, previousCallVersion: null,
                newCall: false, announcementsEnabled: false, pollTimer: null,
                get recentSlots() {
                    return [...this.recentCalls.slice(0, 3), ...Array(Math.max(0, 3 - this.recentCalls.length)).fill(null)];
                },
                async init() {
                    await this.refresh(false);
                    this.pollTimer = window.setInterval(() => this.refresh(true), 3000);
                    window.addEventListener('beforeunload', () => window.clearInterval(this.pollTimer), { once: true });
                },
                async refresh(announceNew) {
                    try {
                        const response = await fetch('/api/queue/calling-board', { headers: { Accept: 'application/json' } });
                        if (!response.ok) throw new Error('Unable to load the calling board.');
                        const data = await response.json();
                        this.queueActive = data.queue_active !== false;
                        const nextCall = data.current_call || null;
                        const isNewCall = announceNew && this.queueActive && nextCall &&
                            (nextCall.id !== this.previousCallId || nextCall.call_version !== this.previousCallVersion);
                        this.currentCall = nextCall;
                        this.recentCalls = data.recent_calls || [];
                        this.waitingCount = data.waiting_count || 0;
                        if (isNewCall) {
                            this.newCall = true;
                            window.setTimeout(() => { this.newCall = false; }, 4000);
                            if (this.announcementsEnabled) this.announce(nextCall);
                        }
                        this.previousCallId = nextCall ? nextCall.id : null;
                        this.previousCallVersion = nextCall ? nextCall.call_version : null;
                        this.lastUpdated = new Date(data.updated_at || Date.now()).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        this.connectionError = false;
                        this.loading = false;
                    } catch (error) {
                        this.connectionError = true;
                        this.loading = false;
                    }
                },
                ticketLabel(number) { return `#${String(number).padStart(3, '0')}`; },
                counterLabel(ticket) { return ticket.barber ? `Barber ${ticket.barber}` : 'Please see a barber'; },
                toggleAnnouncements() {
                    this.announcementsEnabled = !this.announcementsEnabled;
                    if (this.announcementsEnabled && 'speechSynthesis' in window) window.speechSynthesis.cancel();
                },
                announce(ticket) {
                    if (!('speechSynthesis' in window)) return;
                    window.speechSynthesis.cancel();
                    window.speechSynthesis.speak(new SpeechSynthesisUtterance(`${this.ticketLabel(ticket.queue_number)}, please proceed to ${this.counterLabel(ticket)}.`));
                }
            };
        }
    </script>
@endsection
