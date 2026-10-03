<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Calling Board' }} · Sidecut</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            const savedTheme = localStorage.getItem('theme');
            const defaultTheme = 'dark'; // Dark is Sidecut's main theme; the toggle saves a preference.
            document.documentElement.classList.toggle('dark', (savedTheme || defaultTheme) === 'dark');
        })();
    </script>
</head>
<body class="h-full overflow-hidden bg-gray-100 text-gray-800 antialiased dark:bg-gray-950 dark:text-white/90">
    @yield('content')
    @stack('scripts')
</body>
</html>
