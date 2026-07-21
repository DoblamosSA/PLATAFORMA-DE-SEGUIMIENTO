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
        <div class="min-h-full lg:grid lg:grid-cols-2 page-enter">

            {{-- Panel de marca con imagen de fondo --}}
            <div class="relative hidden lg:flex items-end justify-center overflow-hidden text-white bg-cover bg-center"
                 style="background-image: url('/storage/assets/image/wall-login.png'); background-size: cover; background-position: center;">

                <p class="absolute bottom-6 left-1/2 -translate-x-1/2 text-xs text-white/60">© {{ date('Y') }} Projects · Direccion de Tecnologia</p>
            </div>

            {{-- Formulario --}}
            <div class="flex min-h-full items-center justify-center bg-white dark:bg-slate-950 px-6 py-12 sm:px-10"
                 style="background-image: radial-gradient(40rem 40rem at 100% 0%, rgba(59,130,246,0.06), transparent 60%);">
                <div class="w-full max-w-md anim-fade-up">
                    <div class="mb-8 lg:hidden flex items-center gap-3">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-sky-600 text-white font-bold">P</span>
                        <span class="text-lg font-semibold text-slate-800 dark:text-slate-100">Projects</span>
                    </div>

                    <h1 class="hidden lg:block text-4xl font-bold tracking-tight text-slate-800 dark:text-slate-100">Projects</h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Otra forma de administrar tus proyectos</p>

                    <div class="mt-8">
                        {{ $slot }}
                    </div>

                    <p class="mt-10 text-center text-xs text-slate-400 dark:text-slate-600">© {{ date('Y') }} Projects Solutions. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </body>
</html>
