<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Anti-FOUC: aplica el tema antes del primer pintado (preferencia guardada o del sistema) --}}
        <script>
            (function () {
                var stored = localStorage.getItem('theme');
                var dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);
            })();
        </script>

        <title>{{ config('app.name', 'Projects') }}</title>

        {{-- PWA --}}
        <link rel="manifest" href="/manifest.webmanifest">
        <meta name="theme-color" content="#2563eb">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">
        <link rel="icon" type="image/png" href="/icons/icon-192.png">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans antialiased">
        <div class="min-h-full flex items-center justify-center bg-slate-50 dark:bg-slate-950 px-6 py-12 page-enter"
             style="background-image: radial-gradient(50rem 50rem at 50% 0%, rgba(59,130,246,0.10), transparent 60%);">
            <div class="w-full max-w-4xl anim-fade-up">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
