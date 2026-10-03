<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} · Sidecut</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Apply dark mode immediately to prevent flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const defaultTheme = 'dark'; // Dark is Sidecut's main theme; the toggle saves a preference.
            document.documentElement.classList.toggle('dark', (savedTheme || defaultTheme) === 'dark');
        })();
    </script>

    <script>
        // Small display helpers shared by every Sidecut screen.
        window.sc = {
            ticket(number) {
                return '#' + String(number ?? '').padStart(3, '0');
            },
            initials(name) {
                const parts = String(name || '?').trim().split(/\s+/).filter(Boolean);
                if (parts.length === 0) return '?';
                return (parts[0][0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
            },
            // Stable colour per person so the same barber/customer always looks the same.
            tone(seed) {
                const tones = [
                    'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300',
                    'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
                    'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400',
                    'bg-blue-light-50 text-blue-light-700 dark:bg-blue-light-500/15 dark:text-blue-light-400',
                    'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
                    'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400',
                ];
                const text = String(seed ?? '');
                let hash = 0;
                for (let i = 0; i < text.length; i++) hash = (hash * 31 + text.charCodeAt(i)) >>> 0;
                return tones[hash % tones.length];
            },
            time(value) {
                return value ? new Date(value).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—';
            },
            minutesSince(value, now = Date.now()) {
                return value ? Math.max(0, Math.floor((now - new Date(value).getTime()) / 60000)) : 0;
            },
            duration(minutes) {
                if (minutes < 60) return minutes + ' min';
                return Math.floor(minutes / 60) + 'h ' + String(minutes % 60).padStart(2, '0') + 'm';
            },
        };

        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                theme: 'light',
                init() {
                    const savedTheme = localStorage.getItem('theme');
                    const defaultTheme = 'dark'; // Dark is Sidecut's main theme; the toggle saves a preference.
                    this.theme = savedTheme || defaultTheme;
                    this.updateTheme();
                },
                toggle() {
                    this.theme = this.theme === 'light' ? 'dark' : 'light';
                    localStorage.setItem('theme', this.theme);
                    this.updateTheme();
                },
                updateTheme() {
                    document.documentElement.classList.toggle('dark', this.theme === 'dark');
                }
            });

            Alpine.store('sidebar', {
                isExpanded: window.innerWidth >= 1280,
                isMobileOpen: false,
                isHovered: false,
                get isOpen() {
                    return this.isExpanded || this.isHovered || this.isMobileOpen;
                },
                toggleExpanded() {
                    this.isExpanded = !this.isExpanded;
                    this.isMobileOpen = false;
                },
                toggleMobileOpen() {
                    this.isMobileOpen = !this.isMobileOpen;
                },
                setMobileOpen(val) {
                    this.isMobileOpen = val;
                },
                setHovered(val) {
                    if (window.innerWidth >= 1280 && !this.isExpanded) {
                        this.isHovered = val;
                    }
                }
            });

            // Non-blocking feedback: Alpine.store('toast').push('Saved')
            Alpine.store('toast', {
                items: [],
                nextId: 1,
                push(message, type = 'success', title = null) {
                    const id = this.nextId++;
                    this.items.push({ id, message, type, title });
                    if (this.items.length > 4) this.items.shift();
                    window.setTimeout(() => this.remove(id), type === 'error' ? 7000 : 3500);
                },
                remove(id) {
                    this.items = this.items.filter(item => item.id !== id);
                }
            });

            // Promise-based confirmation: if (await Alpine.store('confirm').ask({...})) { ... }
            Alpine.store('confirm', {
                open: false,
                title: '',
                message: '',
                confirmText: 'Confirm',
                cancelText: 'Cancel',
                danger: true,
                resolver: null,
                ask(options) {
                    this.title = options.title || 'Are you sure?';
                    this.message = options.message || '';
                    this.confirmText = options.confirmText || 'Confirm';
                    this.cancelText = options.cancelText || 'Cancel';
                    this.danger = options.danger !== false;
                    this.open = true;
                    return new Promise(resolve => { this.resolver = resolve; });
                },
                answer(value) {
                    this.open = false;
                    if (this.resolver) this.resolver(value);
                    this.resolver = null;
                }
            });
        });
    </script>
</head>

<body class="bg-gray-50 text-gray-800 antialiased dark:bg-gray-900 dark:text-white/90"
    x-data
    x-init="
    const checkMobile = () => {
        if (window.innerWidth < 1280) {
            $store.sidebar.setMobileOpen(false);
            $store.sidebar.isExpanded = false;
        } else {
            $store.sidebar.isMobileOpen = false;
            $store.sidebar.isExpanded = true;
        }
    };
    window.addEventListener('resize', checkMobile);">

    <div class="min-h-screen xl:flex">
        @include('layouts.sidebar')

        {{-- Margin only animates after first paint, so charts measure their final width on load. --}}
        <div class="min-w-0 flex-1 ease-in-out"
            x-data="{ animate: false }" x-init="setTimeout(() => animate = true, 500)"
            :class="{
                'transition-all duration-300': animate,
                'xl:ml-[264px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                'xl:ml-[84px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
            }">
            @include('layouts.app-header')
            <main class="mx-auto max-w-(--breakpoint-2xl) px-4 pb-10 pt-5 md:px-6 md:pt-7">
                @yield('content')
            </main>
        </div>
    </div>

    <x-common.toast-stack />
    <x-common.confirm-dialog />

    @stack('scripts')
</body>

</html>
