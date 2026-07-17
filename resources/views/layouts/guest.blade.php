<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Gestion TI') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans antialiased">
        <div class="min-h-full lg:grid lg:grid-cols-2">

            {{-- Panel de marca (degradado) --}}
            <div class="relative hidden lg:flex flex-col justify-between overflow-hidden p-12 text-white"
                 style="background: linear-gradient(160deg, #1e1b4b 0%, #4338ca 55%, #7c3aed 100%);">
                <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute -left-16 bottom-0 h-72 w-72 rounded-full bg-fuchsia-500/20 blur-3xl"></div>

                <div class="relative flex items-center gap-3">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 backdrop-blur font-bold text-lg">TI</span>
                    <span class="text-lg font-semibold tracking-tight">Gestion TI</span>
                </div>

                <div class="relative">
                    <h1 class="text-4xl font-bold leading-tight">Proyectos y actividades<br>de tecnologia, bajo control.</h1>
                    <p class="mt-4 max-w-md text-indigo-200">Asigna tareas a tu equipo, mide el cumplimiento del SLA y reporta resultados a gerencia — todo en un solo lugar.</p>

                    <div class="mt-8 grid grid-cols-3 gap-4 max-w-md">
                        <div class="rounded-xl bg-white/10 p-3 backdrop-blur">
                            <p class="text-2xl font-bold">SLA</p>
                            <p class="text-[11px] text-indigo-200">Cumplimiento en vivo</p>
                        </div>
                        <div class="rounded-xl bg-white/10 p-3 backdrop-blur">
                            <p class="text-2xl font-bold">Equipos</p>
                            <p class="text-[11px] text-indigo-200">Por proyecto</p>
                        </div>
                        <div class="rounded-xl bg-white/10 p-3 backdrop-blur">
                            <p class="text-2xl font-bold">PDF</p>
                            <p class="text-[11px] text-indigo-200">Reporte gerencia</p>
                        </div>
                    </div>
                </div>

                <p class="relative text-xs text-indigo-300/70">© {{ date('Y') }} Gestion TI · Direccion de Tecnologia</p>
            </div>

            {{-- Formulario --}}
            <div class="flex min-h-full items-center justify-center bg-slate-100 px-6 py-12"
                 style="background-image: radial-gradient(40rem 40rem at 100% 0%, rgba(99,102,241,0.10), transparent 60%);">
                <div class="w-full max-w-md">
                    <div class="mb-8 lg:hidden flex items-center gap-3">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white font-bold">TI</span>
                        <span class="text-lg font-semibold text-slate-800">Gestion TI</span>
                    </div>

                    <div class="rounded-2xl border border-slate-200/70 bg-white/90 backdrop-blur p-8 shadow-xl shadow-slate-300/40">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
