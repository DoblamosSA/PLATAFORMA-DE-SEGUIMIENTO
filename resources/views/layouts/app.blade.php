<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Gestion TI') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans antialiased text-slate-700">
        <div class="min-h-full bg-slate-100/80"
             style="background-image: radial-gradient(60rem 60rem at 110% -10%, rgba(99,102,241,0.08), transparent 60%), radial-gradient(50rem 50rem at -10% 110%, rgba(139,92,246,0.07), transparent 55%);">

            {{-- Sidebar + barra movil (componente Livewire con logout) --}}
            <livewire:layout.navigation />

            {{-- Contenido --}}
            <div class="lg:pl-64">
                <main class="min-h-screen">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
