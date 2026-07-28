<?php

use App\Domain\Organization\Services\RoleContextService;
use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        // Redireccion completa (sin wire:navigate): descarta el contexto JS y
        // el cache de snapshots de Livewire, para que "atras" no pueda
        // restaurar una pantalla autenticada desde memoria.
        $this->redirect('/');
    }

    public function cambiarRol(RoleContextService $roleContext): void
    {
        $roleContext->clear();

        $this->redirect(route('role.choose'), navigate: true);
    }
}; ?>

@php
    $u = auth()->user();
    $puedeCambiarRol = app(RoleContextService::class)->hasChoice($u);
    $nav = [
        ['route' => 'dashboard',              'pattern' => 'dashboard',   'label' => 'Dashboard', 'icon' => 'dashboard'],
        ['route' => 'proyectos',              'pattern' => 'proyectos*',  'label' => 'Proyectos', 'icon' => 'folder'],
    ];
    // Cada item del menu se arma segun el permiso granular efectivo del rol
    // (primario o heredado) del usuario. El bypass universal es solo
    // esSuperAdmin(), ya cubierto automaticamente por Gate::before arriba -
    // el enum legado 'admin'/esAdmin() ya no da acceso libre por si solo.
    if (\Illuminate\Support\Facades\Gate::allows('tasks.view')) {
        $nav[] = ['route' => 'tareas',        'pattern' => 'tareas*',     'label' => 'Tareas',    'icon' => 'tasks'];
    }
    $nav[] = ['route' => 'informes.cumplimiento', 'pattern' => 'informes*', 'label' => 'Informes', 'icon' => 'report'];
    if (\Illuminate\Support\Facades\Gate::allows('users.view')) {
        $nav[] = ['route' => 'colaboradores', 'pattern' => 'colaboradores*', 'label' => 'Colaboradores', 'icon' => 'users'];
    }
    if (\Illuminate\Support\Facades\Gate::allows('departments.view')) {
        $nav[] = ['route' => 'departamentos', 'pattern' => 'departamentos*', 'label' => 'Departamentos', 'icon' => 'building'];
    }
    if (\Illuminate\Support\Facades\Gate::allows('subdepartments.view')) {
        $nav[] = ['route' => 'subdepartamentos', 'pattern' => 'subdepartamentos*', 'label' => 'SubDepartamentos', 'icon' => 'sitemap'];
    }
    if (\Illuminate\Support\Facades\Gate::allows('roles.view')) {
        $nav[] = ['route' => 'roles', 'pattern' => 'roles*', 'label' => 'Roles', 'icon' => 'shield-check'];
        $nav[] = ['route' => 'permisos', 'pattern' => 'permisos*', 'label' => 'Permisos', 'icon' => 'key'];
    }
@endphp

<div x-data="{ open: false }">
    {{-- Barra superior movil --}}
    <div class="lg:hidden sticky top-0 z-30 flex items-center justify-between bg-slate-900 px-4 py-3 text-white shadow-lg">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-sky-600 font-bold">P</span>
            <span class="font-semibold tracking-tight">Projects</span>
        </div>
        <div class="flex items-center gap-1">
            <button @click="$store.theme.toggle()" :aria-pressed="$store.theme.dark.toString()" aria-label="Cambiar tema claro/oscuro"
                    class="p-1.5 rounded-lg hover:bg-white/10 active:scale-95 transition">
                <x-icon x-show="!$store.theme.dark" name="sun" class="w-5 h-5" />
                <x-icon x-show="$store.theme.dark" name="moon" class="w-5 h-5" />
            </button>
            <button @click="open = true" aria-label="Abrir menu" class="p-1.5 rounded-lg hover:bg-white/10 active:scale-95 transition"><x-icon name="menu" class="w-6 h-6" /></button>
        </div>
    </div>

    {{-- Overlay movil --}}
    <div x-show="open" x-transition.opacity @click="open = false"
         class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden" style="display:none;"></div>

    {{-- Sidebar: en movil se desliza; en desktop se colapsa a iconos --}}
    <aside class="sidebar-panel fixed inset-y-0 left-0 z-50 w-64 transform transition-all duration-300 ease-in-out lg:translate-x-0"
           :class="[
               open ? 'translate-x-0' : '-translate-x-full',
               $store.sidebar.collapsed ? 'lg:w-20' : 'lg:w-64',
           ]"
           style="background: linear-gradient(180deg, #0f172a 0%, #172554 100%);">

        {{-- Boton al borde derecho del sidebar (solo desktop) --}}
        <button type="button" @click="$store.sidebar.toggle()"
                class="hidden lg:flex absolute top-8 -right-3 z-[60] h-7 w-7 items-center justify-center rounded-full
                       border border-slate-600/80 bg-slate-800 text-slate-200 shadow-lg shadow-slate-950/40
                       hover:bg-blue-600 hover:border-blue-500 hover:text-white hover:scale-105
                       active:scale-95 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                :title="$store.sidebar.collapsed ? 'Expandir menú' : 'Colapsar menú'"
                :aria-label="$store.sidebar.collapsed ? 'Expandir menú' : 'Colapsar menú'"
                :aria-expanded="(!$store.sidebar.collapsed).toString()">
            <x-icon x-show="!$store.sidebar.collapsed" name="chevron-left" class="w-4 h-4" />
            <x-icon x-show="$store.sidebar.collapsed" name="chevron-right" class="w-4 h-4" style="display:none;" />
        </button>

        <div class="flex h-full flex-col overflow-y-auto overflow-x-hidden py-6 text-slate-300"
             :class="$store.sidebar.collapsed ? 'lg:px-2 px-4' : 'px-4'">
            {{-- Marca --}}
            <div class="flex items-center justify-between px-2"
                 :class="$store.sidebar.collapsed && 'lg:justify-center'">
                <div class="flex items-center gap-3 min-w-0"
                     :class="$store.sidebar.collapsed && 'lg:justify-center'">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-sky-600 text-white font-bold shadow-lg shadow-blue-900/50">P</span>
                    <div class="leading-tight overflow-hidden transition-all duration-300"
                         :class="$store.sidebar.collapsed ? 'lg:w-0 lg:opacity-0 lg:invisible' : 'w-auto opacity-100'">
                        <p class="font-semibold text-white whitespace-nowrap">Projects</p>
                        <p class="text-[11px] text-slate-400 whitespace-nowrap">Proyectos & Departamentos</p>
                    </div>
                </div>
                <button @click="open = false" class="lg:hidden p-1 rounded-lg hover:bg-white/10 text-slate-400" aria-label="Cerrar menu">
                    <x-icon name="close" class="w-5 h-5" />
                </button>
            </div>

            {{-- Navegacion --}}
            <nav class="mt-8 flex-1 space-y-1">
                <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500 overflow-hidden transition-all duration-300"
                   :class="$store.sidebar.collapsed ? 'lg:h-0 lg:opacity-0 lg:pb-0 lg:invisible' : ''">Menu</p>
                @foreach ($nav as $item)
                    @php $active = request()->routeIs($item['pattern']); @endphp
                    <a href="{{ route($item['route']) }}" wire:navigate @click="open = false"
                       title="{{ $item['label'] }}"
                       class="group relative flex items-center rounded-xl py-2.5 text-sm font-medium transition
                              {{ $active
                                    ? 'bg-white/10 text-white shadow-inner'
                                    : 'text-slate-400 hover:bg-white/5 hover:text-white' }}"
                       :class="$store.sidebar.collapsed ? 'lg:justify-center lg:px-0 px-3 gap-3' : 'gap-3 px-3'">
                        @if ($active)
                            <span class="absolute left-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-gradient-to-b from-blue-400 to-sky-500"></span>
                        @endif
                        <x-icon :name="$item['icon']" class="w-5 h-5 shrink-0 {{ $active ? 'text-blue-300' : 'text-slate-500 group-hover:text-slate-300' }}" />
                        <span class="truncate overflow-hidden transition-all duration-300"
                              :class="$store.sidebar.collapsed ? 'lg:w-0 lg:opacity-0 lg:invisible' : 'w-auto opacity-100'">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            {{-- Notificaciones push: visible hasta que el usuario decida --}}
            <div x-data="{
                     permiso: ('Notification' in window) ? Notification.permission : 'unsupported',
                     ios: /iphone|ipad|ipod/i.test(navigator.userAgent),
                     standalone: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
                 }"
                 x-show="permiso === 'default' || permiso === 'denied' || (ios && !standalone)" x-cloak class="mt-4"
                 :class="$store.sidebar.collapsed && 'lg:hidden'">
                <button x-show="permiso === 'default' && !(ios && !standalone)"
                        @click="permiso = await window.activarNotificaciones()"
                        class="flex w-full items-center gap-3 rounded-xl border border-blue-400/30 bg-blue-500/10 px-3 py-2.5 text-sm font-medium text-blue-200 hover:bg-blue-500/20 active:scale-[0.98] transition">
                    <x-icon name="bell" class="w-5 h-5 text-blue-300" />
                    Activar notificaciones
                </button>
                <p x-show="ios && !standalone" class="flex items-start gap-2 rounded-xl bg-white/5 px-3 py-2.5 text-[11px] leading-snug text-slate-400">
                    <x-icon name="bell" class="w-4 h-4 shrink-0 mt-0.5 text-slate-500" />
                    En iPhone/iPad: instala la app (Compartir → Añadir a pantalla de inicio) para recibir notificaciones.
                </p>
                <p x-show="permiso === 'denied' && !(ios && !standalone)" class="flex items-start gap-2 rounded-xl bg-white/5 px-3 py-2.5 text-[11px] leading-snug text-slate-400">
                    <x-icon name="bell" class="w-4 h-4 shrink-0 mt-0.5 text-slate-500" />
                    Notificaciones bloqueadas: habilítalas en el candado de la barra de direcciones.
                </p>
            </div>

            {{-- Instalar como PWA --}}
            <div x-data="{
                     instalable: window.pwaDisponible?.() ?? false,
                     ios: /iphone|ipad|ipod/i.test(navigator.userAgent),
                     standalone: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
                     verIos: false,
                 }"
                 x-init="window.addEventListener('pwa-instalable', () => instalable = true);
                         window.addEventListener('pwa-instalada', () => instalable = false)"
                 x-show="!standalone && (instalable || ios)" x-cloak class="mt-4"
                 :class="$store.sidebar.collapsed && 'lg:hidden'">
                <button x-show="instalable"
                        @click="if (await window.instalarPWA()) instalable = false"
                        class="flex w-full items-center gap-3 rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-3 py-2.5 text-sm font-medium text-emerald-200 hover:bg-emerald-500/20 active:scale-[0.98] transition">
                    <x-icon name="download" class="w-5 h-5 text-emerald-300" />
                    Instalar aplicación
                </button>
                <div x-show="!instalable && ios">
                    <button @click="verIos = !verIos"
                            class="flex w-full items-center gap-3 rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-3 py-2.5 text-sm font-medium text-emerald-200 hover:bg-emerald-500/20 active:scale-[0.98] transition">
                        <x-icon name="download" class="w-5 h-5 text-emerald-300" />
                        Instalar aplicación
                    </button>
                    <p x-show="verIos" x-transition class="mt-2 rounded-xl bg-white/5 px-3 py-2.5 text-[11px] leading-snug text-slate-400">
                        En iPhone/iPad: toca <span class="text-slate-200">Compartir</span> (el cuadro con flecha) y luego
                        <span class="text-slate-200">Añadir a pantalla de inicio</span>.
                    </p>
                </div>
            </div>

            {{-- Usuario + logout --}}
            <div class="mt-4 border-t border-white/10 pt-4">
                <div class="flex items-center rounded-xl bg-white/5 py-2.5"
                     :class="$store.sidebar.collapsed ? 'lg:flex-col lg:gap-2 lg:px-1 px-3 gap-3' : 'gap-3 px-3'">
                    <x-avatar :usuario="$u" size="h-9 w-9" text="text-sm" />
                    <div class="min-w-0 flex-1 overflow-hidden transition-all duration-300"
                         :class="$store.sidebar.collapsed ? 'lg:w-0 lg:h-0 lg:opacity-0 lg:invisible' : ''">
                        <p class="truncate text-sm font-medium text-white">{{ $u->name }}</p>
                        <p class="truncate text-[11px] capitalize text-slate-400">{{ $u->cargo ?? $u->rol }}</p>
                    </div>
                    <div class="flex items-center gap-0.5"
                         :class="$store.sidebar.collapsed && 'lg:flex-col'">
                        <button @click="$store.theme.toggle()" :aria-pressed="$store.theme.dark.toString()" aria-label="Cambiar tema claro/oscuro" title="Cambiar tema"
                                class="p-1.5 rounded-lg text-slate-400 hover:bg-white/10 hover:text-white active:scale-95 transition">
                            <x-icon x-show="!$store.theme.dark" name="sun" class="w-5 h-5" />
                            <x-icon x-show="$store.theme.dark" name="moon" class="w-5 h-5" />
                        </button>
                        @if ($puedeCambiarRol)
                            <button wire:click="cambiarRol" title="Cambiar rol" aria-label="Cambiar rol"
                                    class="p-1.5 rounded-lg text-slate-400 hover:bg-white/10 hover:text-white active:scale-95 transition"
                                    :class="$store.sidebar.collapsed && 'lg:hidden'">
                                <x-icon name="shield-check" class="w-5 h-5" />
                            </button>
                        @endif
                        <button wire:click="logout" title="Cerrar sesion" aria-label="Cerrar sesion"
                                class="p-1.5 rounded-lg text-slate-400 hover:bg-white/10 hover:text-rose-300 active:scale-95 transition">
                            <x-icon name="logout" class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</div>
