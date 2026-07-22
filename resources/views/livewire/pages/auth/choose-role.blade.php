<?php

use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Services\RoleContextService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest-centered')] class extends Component
{
    /** @var array<int, array{key: string, label: string, type: string, role_id: ?int, department_id: ?int, icon: string, gradient: string}> */
    public array $candidates = [];

    public function mount(RoleContextService $roleContext): void
    {
        if (! $roleContext->hasChoice(Auth::user())) {
            $this->redirect(route('dashboard', absolute: false), navigate: true);

            return;
        }

        $this->candidates = collect($roleContext->candidates(Auth::user()))
            ->map(fn (array $c) => [...$c, ...$this->visualFor($c)])
            ->all();
    }

    public function elegir(string $key, RoleContextService $roleContext): void
    {
        $roleContext->activate(Auth::user(), $key);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    /** @return array{icon: string, gradient: string} */
    private function visualFor(array $candidato): array
    {
        $slug = $candidato['role_id'] ? Role::find($candidato['role_id'])?->slug : null;

        return match (true) {
            $slug === 'super-admin' => ['icon' => 'server', 'gradient' => 'from-violet-600 to-purple-700'],
            $candidato['type'] === 'department' => ['icon' => 'building', 'gradient' => 'from-teal-600 to-cyan-700'],
            default => ['icon' => 'briefcase', 'gradient' => 'from-blue-600 to-sky-700'],
        };
    }
}; ?>

<div class="flex flex-col items-center text-center">
    <div class="flex items-center gap-2 mb-6">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-sky-600 text-white font-bold text-sm">P</span>
        <span class="text-base font-semibold text-slate-700 dark:text-slate-200">{{ config('app.name', 'Projects') }}</span>
    </div>

    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-slate-800 dark:text-slate-100">¿Cómo deseas iniciar?</h1>
    <p class="mt-3 max-w-xl text-sm text-slate-500 dark:text-slate-400">
        Tu cuenta tiene más de un rol asignado. Selecciona con cuál quieres trabajar en esta sesión.
    </p>

    <div class="mt-10 grid gap-5 w-full" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        @foreach ($candidates as $candidato)
            <button type="button" wire:click="elegir('{{ $candidato['key'] }}')"
                    class="group relative flex flex-col items-center justify-end overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-gradient-to-br {{ $candidato['gradient'] }} aspect-[4/3] text-white shadow-lg transition hover:scale-[1.02] hover:shadow-xl active:scale-[0.99] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
                <span class="absolute inset-0 bg-black/10 group-hover:bg-black/0 transition"></span>
                <x-icon :name="$candidato['icon']" class="absolute top-1/2 -translate-y-1/2 w-14 h-14 text-white/20" />
                <span class="relative z-10 pb-5 text-base font-semibold">{{ $candidato['label'] }}</span>
            </button>
        @endforeach
    </div>

    <p class="mt-10 text-xs text-slate-400 dark:text-slate-600">
        ¿Necesitas ayuda con tu acceso? Contacta a soporte.
    </p>
</div>
