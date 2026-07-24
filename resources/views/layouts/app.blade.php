<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full{{ request()->cookie('projects_theme') === 'dark' ? ' dark' : '' }}">
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
                document.cookie = 'projects_theme=' + (dark ? 'dark' : 'light') + '; path=/; max-age=31536000; SameSite=Lax';
            })();
        </script>

        <title>{{ config('app.name', 'Projects') }}</title>

        {{-- PWA --}}
        <link rel="manifest" href="/manifest.webmanifest">
        <meta name="theme-color" content="#2563eb">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">
        <link rel="icon" type="image/png" href="/icons/icon-192.png">
        @if (config('services.webpush.public_key'))
            <meta name="vapid-public-key" content="{{ config('services.webpush.public_key') }}">
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    {{-- El color de fondo vive tambien en el <body> (no solo en el contenedor
         interno): al navegar con wire:navigate, salir de una pantalla pesada
         (ej. dashboard) desmonta y reconstruye el DOM, y sin fondo en el body
         se veria un fotograma blanco por defecto antes de pintar el contenedor
         (pantallazo blanco en modo oscuro). --}}
    <body class="h-full font-sans antialiased text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-950">
        <div class="min-h-full bg-slate-100/80 dark:bg-slate-950"
             style="background-image: radial-gradient(60rem 60rem at 110% -10%, rgba(59,130,246,0.08), transparent 60%), radial-gradient(50rem 50rem at -10% 110%, rgba(14,165,233,0.07), transparent 55%);">

            {{-- Sidebar + barra movil (componente Livewire con logout) --}}
            <livewire:layout.navigation />

            {{-- Contenido --}}
            <div class="lg:pl-64">
                <main id="main-content" class="min-h-screen page-enter">
                    {{ $slot }}
                </main>
            </div>

            <x-confirm-modal />
            <x-toast-notifications />
        </div>
    </body>
</html>
