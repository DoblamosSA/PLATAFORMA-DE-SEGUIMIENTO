<?php

use App\Domain\Organization\Services\RoleContextService;
use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(RoleContextService $roleContext): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        if ($roleContext->hasChoice(Auth::user())) {
            $this->redirect(route('role.choose', absolute: false), navigate: true);

            return;
        }

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-5" x-data="{ verPassword: false }">
        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1.5">Email corporativo</label>
            <div class="relative">
                <x-icon name="mail" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                <input wire:model="form.email" id="email" type="email" name="email" required autofocus autocomplete="username"
                       placeholder="nombre@empresa.com"
                       class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 pl-10 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Contraseña</label>
                @if (Route::has('password.request'))
                    <a class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline" href="{{ route('password.request') }}" wire:navigate>
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>
            <div class="relative">
                <x-icon name="lock" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                <input wire:model="form.password" id="password" :type="verPassword ? 'text' : 'password'" name="password"
                       required autocomplete="current-password" placeholder="••••••••"
                       class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 pl-10 pr-10 focus:border-blue-500 focus:ring-blue-500">
                <button type="button" @click="verPassword = !verPassword"
                        :aria-label="verPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'" :aria-pressed="verPassword.toString()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 rounded">
                    <x-icon x-show="!verPassword" name="eye" class="w-5 h-5" />
                    <x-icon x-show="verPassword" name="eye-slash" class="w-5 h-5" style="display:none;" />
                </button>
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <label for="remember" class="flex items-center gap-2 cursor-pointer select-none">
            <input wire:model="form.remember" id="remember" type="checkbox" name="remember"
                   class="rounded border-slate-300 dark:border-slate-600 dark:bg-slate-800 text-blue-600 focus:ring-blue-500">
            <span class="text-sm text-slate-600 dark:text-slate-400">Mantener sesión iniciada</span>
        </label>

        <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
            Iniciar Sesión
            <x-icon name="arrow-right" class="w-4 h-4" />
        </button>
    </form>
</div>
