<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $nav = [
        ['route' => 'dashboard',              'pattern' => 'dashboard',   'label' => 'Dashboard', 'icon' => 'dashboard'],
        ['route' => 'proyectos',              'pattern' => 'proyectos*',  'label' => 'Proyectos', 'icon' => 'folder'],
        ['route' => 'tareas',                 'pattern' => 'tareas*',     'label' => 'Tareas',    'icon' => 'tasks'],
        ['route' => 'informes.cumplimiento',  'pattern' => 'informes*',   'label' => 'Informes',  'icon' => 'report'],
    ];
    $u = auth()->user();
    $iniciales = collect(explode(' ', $u->name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
@endphp

<div x-data="{ open: false }">
    {{-- Barra superior movil --}}
    <div class="lg:hidden sticky top-0 z-30 flex items-center justify-between bg-slate-900 px-4 py-3 text-white shadow-lg">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 font-bold">TI</span>
            <span class="font-semibold tracking-tight">Gestion TI</span>
        </div>
        <button @click="open = true" class="p-1.5 rounded-lg hover:bg-white/10"><x-icon name="menu" class="w-6 h-6" /></button>
    </div>

    {{-- Overlay movil --}}
    <div x-show="open" x-transition.opacity @click="open = false"
         class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden" style="display:none;"></div>

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-50 w-64 transform overflow-y-auto transition-transform duration-300 lg:translate-x-0"
           :class="open ? 'translate-x-0' : '-translate-x-full'"
           style="background: linear-gradient(180deg, #0f172a 0%, #1e1b4b 100%);">

        <div class="flex h-full flex-col px-4 py-6 text-slate-300">
            {{-- Marca --}}
            <div class="flex items-center justify-between px-2">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white font-bold shadow-lg shadow-indigo-900/50">TI</span>
                    <div class="leading-tight">
                        <p class="font-semibold text-white">Gestion TI</p>
                        <p class="text-[11px] text-slate-400">Proyectos & SLA</p>
                    </div>
                </a>
                <button @click="open = false" class="lg:hidden p-1 rounded-lg hover:bg-white/10 text-slate-400"><x-icon name="close" class="w-5 h-5" /></button>
            </div>

            {{-- Navegacion --}}
            <nav class="mt-8 flex-1 space-y-1">
                <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Menu</p>
                @foreach ($nav as $item)
                    @php $active = request()->routeIs($item['pattern']); @endphp
                    <a href="{{ route($item['route']) }}" wire:navigate @click="open = false"
                       class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                              {{ $active
                                    ? 'bg-white/10 text-white shadow-inner'
                                    : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        @if ($active)
                            <span class="absolute left-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-gradient-to-b from-indigo-400 to-violet-500"></span>
                        @endif
                        <x-icon :name="$item['icon']" class="w-5 h-5 {{ $active ? 'text-indigo-300' : 'text-slate-500 group-hover:text-slate-300' }}" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            {{-- Usuario + logout --}}
            <div class="mt-4 border-t border-white/10 pt-4">
                <div class="flex items-center gap-3 rounded-xl bg-white/5 px-3 py-2.5">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-sm font-semibold text-white uppercase">{{ $iniciales }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ $u->name }}</p>
                        <p class="truncate text-[11px] capitalize text-slate-400">{{ $u->cargo ?? $u->rol }}</p>
                    </div>
                    <button wire:click="logout" title="Cerrar sesion"
                            class="p-1.5 rounded-lg text-slate-400 hover:bg-white/10 hover:text-rose-300 transition">
                        <x-icon name="logout" class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>
    </aside>
</div>
