@if ($enModal)
    <form wire:submit="save" class="space-y-6">
        @include('livewire.organization.roles.partials.campos-formulario')

        @unless ($soloLectura)
            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-5 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                    {{ $role ? 'Guardar cambios' : 'Crear rol' }}
                </button>
                <button type="button" wire:click="cancelar" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">Cancelar</button>
            </div>
        @endunless
    </form>
@else
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-4xl mx-auto space-y-5 anim-fade-up">

            <x-page-header :title="$soloLectura ? 'Ver rol: '.$nombre : ($role ? 'Editar rol' : 'Nuevo rol heredado')"
                            subtitle="Herencia y permisos" icon="shield-check">
                <x-slot:actions>
                    <a href="{{ route('roles') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                        <x-icon name="arrow-left" class="w-4 h-4" /> Volver
                    </a>
                </x-slot:actions>
            </x-page-header>

            <form wire:submit="save" class="rounded-2xl bg-white dark:bg-slate-900 shadow-sm dark:shadow-black/20 border border-slate-200/70 dark:border-slate-800 p-6 space-y-6">
                @include('livewire.organization.roles.partials.campos-formulario')

                @unless ($soloLectura)
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                                class="rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-5 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition">
                            {{ $role ? 'Guardar cambios' : 'Crear rol' }}
                        </button>
                        <a href="{{ route('roles') }}" wire:navigate class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">Cancelar</a>
                    </div>
                @endunless
            </form>
        </div>
    </div>
@endif
