@extends('layouts.guest')

@section('title', 'Join Queue — Sidecut')

@section('content')

<div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-8 dark:bg-gray-950">
    <div class="w-full max-w-[400px]">

        <div x-data="{
            step: 'form',
            form: {
                name: '',
                phone: '',
                barberId: '',
            },
            errors: {},
            queueNumber: null,
            estimatedWait: null,
            barbers: [],
            submitting: false,
            serverError: '',
            loadingBarbers: true,

            lookupNumber: '',
            lookupError: '',
            lookupLoading: false,
            lookupResult: null,

            get anyBarberActive() {
                return this.barbers.length > 0;
            },

            async init() {
                await this.loadBarbers();
            },

            async loadBarbers() {
                const res = await fetch('/api/barbers');
                const data = await res.json();
                this.barbers = data
                    .filter(function(b) { return b.is_active; })
                    .map(function(b) {
                        return { id: b.id, name: b.name, role: b.role, active: b.is_active };
                    });
                this.loadingBarbers = false;
            },

            validate() {
                this.errors = {};
                if (!this.form.name.trim()) {
                    this.errors.name = 'Please enter your name.';
                }
                if (!this.form.phone.trim()) {
                    this.errors.phone = 'Please enter your phone number.';
                } else if (!/^[0-9+\-\s()]{7,15}$/.test(this.form.phone.trim())) {
                    this.errors.phone = 'Please enter a valid phone number.';
                }
                return Object.keys(this.errors).length === 0;
            },

            async submitForm() {
                if (!this.anyBarberActive) return;
                if (!this.validate()) return;
                this.submitting = true;
                this.serverError = '';

                const res = await fetch('/api/queue', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        customer_name: this.form.name,
                        customer_phone: this.form.phone,
                        barber_id: this.form.barberId || null
                    })
                });

                this.submitting = false;

                if (!res.ok) {
                    const body = await res.json();
                    this.serverError = body.message || 'Something went wrong. Please try again.';
                    return;
                }

                const data = await res.json();
                this.queueNumber = data.ticket.queue_number;
                this.estimatedWait = data.estimated_wait_minutes;
                this.step = 'confirmation';
            },

            resetForm() {
                this.form = { name: '', phone: '', barberId: '' };
                this.errors = {};
                this.serverError = '';
                this.step = 'form';
            },

            goToLookup() {
                this.lookupNumber = '';
                this.lookupError = '';
                this.lookupResult = null;
                this.step = 'lookup';
            },

            async checkStatus() {
                if (!this.lookupNumber.trim()) {
                    this.lookupError = 'Please enter your queue number.';
                    return;
                }
                this.lookupLoading = true;
                this.lookupError = '';
                this.lookupResult = null;

                const res = await fetch('/api/queue/lookup?queue_number=' + encodeURIComponent(this.lookupNumber.trim()));

                this.lookupLoading = false;

                if (!res.ok) {
                    const body = await res.json();
                    this.lookupError = body.message || 'Could not find that queue number.';
                    return;
                }

                this.lookupResult = await res.json();
            }
        }">

            <div class="mb-6 text-center">
                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-500 shadow-theme-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none">
                        <path d="M6 6l12 12M18 6L6 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="6" cy="6" r="2.5" stroke="white" stroke-width="1.5"/>
                        <circle cx="6" cy="18" r="2.5" stroke="white" stroke-width="1.5"/>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-800 dark:text-white/90">Sidecut</h1>
                <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Join the queue in seconds</p>
            </div>

            <!-- Loading state -->
            <template x-if="loadingBarbers">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
                    <p class="text-theme-sm text-gray-500 dark:text-gray-400">Checking shop status...</p>
                </div>
            </template>

            <!-- Shop closed state -->
            <template x-if="!loadingBarbers && !anyBarberActive && step === 'form'">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-50 dark:bg-red-500/15">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none">
                            <path d="M12 8v4M12 16h.01" stroke="#D92D20" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="9" stroke="#D92D20" stroke-width="2"/>
                        </svg>
                    </div>
                    <h2 class="mb-1 text-lg font-bold text-gray-800 dark:text-white/90">Sorry, we're closed</h2>
                    <p class="mb-5 text-theme-sm text-gray-500 dark:text-gray-400">
                        No servers are available right now. Please check back later or visit us during opening hours.
                    </p>
                    
                </div>
            </template>

            <template x-if="!loadingBarbers && anyBarberActive && step === 'form'">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">

                    <p x-show="serverError" x-text="serverError" class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-theme-xs text-red-600 dark:bg-red-500/15 dark:text-red-400"></p>

                    <div class="mb-4">
                        <label class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                            Full Name
                        </label>
                        <input
                            type="text"
                            x-model="form.name"
                            placeholder="e.g. John Tan"
                            class="h-12 w-full rounded-lg border px-4 text-theme-sm text-gray-800 outline-none focus:ring-3 dark:bg-gray-900 dark:text-white/90"
                            :class="errors.name ? 'border-red-300 focus:border-red-400 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700'" />
                        <p x-show="errors.name" x-text="errors.name" class="mt-1 text-theme-xs text-red-500"></p>
                    </div>

                    <div class="mb-4">
                        <label class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                            Phone Number
                        </label>
                        <input
                            type="tel"
                            x-model="form.phone"
                            placeholder="e.g. 012-345 6789"
                            class="h-12 w-full rounded-lg border px-4 text-theme-sm text-gray-800 outline-none focus:ring-3 dark:bg-gray-900 dark:text-white/90"
                            :class="errors.phone ? 'border-red-300 focus:border-red-400 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700'" />
                        <p x-show="errors.phone" x-text="errors.phone" class="mt-1 text-theme-xs text-red-500"></p>
                        <p x-show="!errors.phone" class="mt-1 text-theme-xs text-gray-400 dark:text-gray-500">
                            We will text you queue updates here.
                        </p>
                    </div>

                    <div class="mb-5">
                        <label class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                            Preferred Server
                            <span class="font-normal text-gray-400">(optional)</span>
                        </label>
                        <select
                            x-model="form.barberId"
                            class="h-12 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">No preference</option>
                            <template x-for="barber in barbers" :key="barber.id">
                                <option :value="barber.id" x-text="barber.name + ' — ' + barber.role"></option>
                            </template>
                        </select>
                    </div>

                    <button
                        @click="submitForm()"
                        type="button"
                        :disabled="submitting"
                        class="mb-3 flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-brand-500 text-theme-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-text="submitting ? 'Joining...' : 'Join Queue'"></span>
                        <svg x-show="!submitting" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    <button
                        @click="goToLookup()"
                        type="button"
                        class="h-11 w-full rounded-lg border border-gray-300 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">
                        Already in queue? Check status
                    </button>

                </div>
            </template>

            <template x-if="step === 'lookup'">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">

                    <div class="mb-4">
                        <label class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">
                            Your Queue Number
                        </label>
                        <input
                            type="text"
                            x-model="lookupNumber"
                            placeholder="e.g. 128"
                            @keydown.enter="checkStatus()"
                            class="h-12 w-full rounded-lg border border-gray-300 px-4 text-theme-sm text-gray-800 outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        <p x-show="lookupError" x-text="lookupError" class="mt-1 text-theme-xs text-red-500"></p>
                    </div>

                    <button
                        @click="checkStatus()"
                        type="button"
                        :disabled="lookupLoading"
                        class="mb-3 flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-brand-500 text-theme-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-text="lookupLoading ? 'Checking...' : 'Check Status'"></span>
                    </button>

                    <template x-if="lookupResult">
                        <div class="mt-4 rounded-xl bg-gray-50 p-5 text-center dark:bg-white/[0.03]">
                            <p class="text-theme-xs uppercase tracking-wide text-gray-400 dark:text-gray-500">Queue Number</p>
                            <p class="mt-1 text-3xl font-bold text-brand-500" x-text="'#' + lookupResult.ticket.queue_number"></p>

                            <p class="mt-3 text-theme-sm font-semibold text-gray-800 dark:text-white/90" x-text="lookupResult.ticket.customer_name"></p>

                            <span class="mt-2 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-theme-xs font-medium"
                                :class="{
                                    'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400': lookupResult.ticket.status === 'in_queue',
                                    'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-400': lookupResult.ticket.status === 'serving',
                                    'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-500': lookupResult.ticket.status === 'completed',
                                    'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-500': lookupResult.ticket.status === 'canceled'
                                }"
                                x-text="({in_queue: 'In Queue', serving: 'Being Served', completed: 'Completed', canceled: 'Canceled'})[lookupResult.ticket.status]">
                            </span>

                            <div x-show="lookupResult.position" class="mt-4 flex items-center justify-center gap-6">
                                <div>
                                    <p class="text-theme-xs text-gray-400 dark:text-gray-500">Position</p>
                                    <p class="text-theme-sm font-semibold text-gray-800 dark:text-white/90" x-text="'#' + lookupResult.position + ' in line'"></p>
                                </div>
                                <div class="h-8 w-px bg-gray-200 dark:bg-gray-800"></div>
                                <div>
                                    <p class="text-theme-xs text-gray-400 dark:text-gray-500">Est. Wait</p>
                                    <p class="text-theme-sm font-semibold text-gray-800 dark:text-white/90" x-text="lookupResult.estimated_wait_minutes + ' min'"></p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <button
                        @click="step = anyBarberActive ? 'form' : 'form'"
                        type="button"
                        class="mt-3 h-11 w-full rounded-lg border border-gray-300 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">
                        Back
                    </button>

                </div>
            </template>

            <template x-if="step === 'confirmation'">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">

                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-50 dark:bg-green-500/15">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none">
                            <path d="M5 13l4 4L19 7" stroke="#039855" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>

                    <h2 class="mb-1 text-lg font-bold text-gray-800 dark:text-white/90">You are in the queue!</h2>
                    <p class="mb-6 text-theme-sm text-gray-500 dark:text-gray-400">
                        We will text <span x-text="form.name"></span> when it is almost your turn.
                    </p>

                    <div class="mb-6 rounded-xl bg-gray-50 p-5 dark:bg-white/[0.03]">
                        <p class="text-theme-xs uppercase tracking-wide text-gray-400 dark:text-gray-500">Your Queue Number</p>
                        <p class="mt-1 text-4xl font-bold text-brand-500" x-text="'#' + queueNumber"></p>
                    </div>

                    <div class="mb-6 flex items-center justify-center gap-6">
                        <div>
                            <p class="text-theme-xs text-gray-400 dark:text-gray-500">Est. Wait</p>
                            <p class="text-theme-sm font-semibold text-gray-800 dark:text-white/90" x-text="estimatedWait + ' min'"></p>
                        </div>
                        <div class="h-8 w-px bg-gray-200 dark:bg-gray-800"></div>
                        <div>
                            <p class="text-theme-xs text-gray-400 dark:text-gray-500">Server</p>
                            <p class="text-theme-sm font-semibold text-gray-800 dark:text-white/90" x-text="form.barberId ? barbers.find(b => b.id == form.barberId).name : 'Any available'"></p>
                        </div>
                    </div>

                    <button
                        @click="resetForm()"
                        type="button"
                        class="h-11 w-full rounded-lg border border-gray-300 text-theme-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.05]">
                        Join Another Queue
                    </button>

                </div>
            </template>

        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    #userDropdown,
    .dropdown-user,
    [data-user-menu] {
        display: none !important;
    }
</style>
@endpush