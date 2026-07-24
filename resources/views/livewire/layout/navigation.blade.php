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

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-50 w-64 transform overflow-y-auto transition-transform duration-300 lg:translate-x-0"
           :class="open ? 'translate-x-0' : '-translate-x-full'"
           style="background: linear-gradient(180deg, #0f172a 0%, #172554 100%);">

        <div class="flex h-full flex-col px-4 py-6 text-slate-300">
            {{-- Marca --}}
            <div class="flex items-center justify-between px-2">
                <div class="flex items-center gap-3 pointer-events-none">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-sky-600 text-white font-bold shadow-lg shadow-blue-900/50">P</span>
                    <div class="leading-tight">
                        <p class="font-semibold text-white">Projects</p>
                        <p class="text-[11px] text-slate-400">Proyectos & Departamentos</p>
                    </div>
                </div>
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
                            <span class="absolute left-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-gradient-to-b from-blue-400 to-sky-500"></span>
                        @endif
                        <x-icon :name="$item['icon']" class="w-5 h-5 {{ $active ? 'text-blue-300' : 'text-slate-500 group-hover:text-slate-300' }}" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            {{-- Notificaciones push: visible hasta que el usuario decida --}}
            <div x-data="{ permiso: ('Notification' in window) ? Notification.permission : 'unsupported' }"
                 x-show="permiso === 'default' || permiso === 'denied'" x-cloak class="mt-4">
                <button x-show="permiso === 'default'"
                        @click="permiso = await window.activarNotificaciones()"
                        class="flex w-full items-center gap-3 rounded-xl border border-blue-400/30 bg-blue-500/10 px-3 py-2.5 text-sm font-medium text-blue-200 hover:bg-blue-500/20 active:scale-[0.98] transition">
                    <x-icon name="bell" class="w-5 h-5 text-blue-300" />
                    Activar notificaciones
                </button>
                <p x-show="permiso === 'denied'" class="flex items-start gap-2 rounded-xl bg-white/5 px-3 py-2.5 text-[11px] leading-snug text-slate-400">
                    <x-icon name="bell" class="w-4 h-4 shrink-0 mt-0.5 text-slate-500" />
                    Notificaciones bloqueadas: habilítalas en el candado de la barra de direcciones.
                </p>
            </div>

            {{-- Instalar como PWA (Android/desktop via prompt nativo; iOS con instrucciones) --}}
            <div x-data="{
                     instalable: window.pwaDisponible?.() ?? false,
                     ios: /iphone|ipad|ipod/i.test(navigator.userAgent),
                     standalone: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
                     verIos: false,
                 }"
                 x-init="window.addEventListener('pwa-instalable', () => instalable = true);
                         window.addEventListener('pwa-instalada', () => instalable = false)"
                 x-show="!standalone && (instalable || ios)" x-cloak class="mt-4">
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
                <div class="flex items-center gap-3 rounded-xl bg-white/5 px-3 py-2.5">
                    <x-avatar :usuario="$u" size="h-9 w-9" text="text-sm" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ $u->name }}</p>
                        <p class="truncate text-[11px] capitalize text-slate-400">{{ $u->cargo ?? $u->rol }}</p>
                    </div>
                    <button @click="$store.theme.toggle()" :aria-pressed="$store.theme.dark.toString()" aria-label="Cambiar tema claro/oscuro" title="Cambiar tema"
                            class="p-1.5 rounded-lg text-slate-400 hover:bg-white/10 hover:text-white active:scale-95 transition">
                        <x-icon x-show="!$store.theme.dark" name="sun" class="w-5 h-5" />
                        <x-icon x-show="$store.theme.dark" name="moon" class="w-5 h-5" />
                    </button>
                    @if ($puedeCambiarRol)
                        <button wire:click="cambiarRol" title="Cambiar rol" aria-label="Cambiar rol"
                                class="p-1.5 rounded-lg text-slate-400 hover:bg-white/10 hover:text-white active:scale-95 transition">
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
    </aside>
</div>
