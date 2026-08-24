@if ($enModal)
    {{-- x-data compartido por todo el formulario: name/email/password son espejos
         locales (el envio real sigue viajando por wire:model, ver x-on:input en
         cada input dentro de campos-formulario.blade.php); depto/subdepto/dias/
         horas son un segundo @entangle independiente de las mismas propiedades
         que ya usan los bloques internos de campos-formulario.blade.php
         (Livewire admite multiples @entangle de la misma propiedad). Solo se usa
         para calcular si el formulario esta completo y habilitar el boton; no
         toca la logica de cascada existente. --}}
    <div x-data="{
            name: @js($name),
            email: @js($email),
            password: '',
            passwordConfirmation: '',
            esEdicion: {{ $colaborador ? 'true' : 'false' }},
            depto: @entangle('department_id'),
            subdepto: @entangle('sub_department_id'),
            rol: @entangle('role_id'),
            dias: @entangle('diasLaborales'),
            horas: @entangle('horasDiarias'),
            get formularioCompleto() {
                const passwordOk = this.esEdicion || (this.password.trim() !== '' && this.passwordConfirmation.trim() !== '');
                return this.name.trim() !== ''
                    && this.email.trim() !== ''
                    && passwordOk
                    && this.depto !== ''
                    && this.subdepto !== ''
                    && this.rol !== ''
                    && Array.isArray(this.dias) && this.dias.length > 0
                    && this.horas !== null && this.horas !== '';
            }
         }">
        <form wire:submit="save" class="space-y-6">
            @include('livewire.colaboradores.partials.campos-formulario')

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" wire:loading.attr="disabled" wire:target="save" :disabled="!formularioCompleto"
                        class="rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-5 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="save">{{ $colaborador ? 'Guardar cambios' : 'Crear colaborador' }}</span>
                    <span wire:loading wire:target="save">Guardando…</span>
                </button>
                <button type="button" wire:click="cancelar" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">Cancelar</button>
            </div>
        </form>
    </div>
@else
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-3xl mx-auto space-y-5 anim-fade-up"
             x-data="{
                name: @js($name),
                email: @js($email),
                password: '',
                passwordConfirmation: '',
                esEdicion: {{ $colaborador ? 'true' : 'false' }},
                depto: @entangle('department_id'),
                subdepto: @entangle('sub_department_id'),
                rol: @entangle('role_id'),
                dias: @entangle('diasLaborales'),
                horas: @entangle('horasDiarias'),
                get formularioCompleto() {
                    const passwordOk = this.esEdicion || (this.password.trim() !== '' && this.passwordConfirmation.trim() !== '');
                    return this.name.trim() !== ''
                        && this.email.trim() !== ''
                        && passwordOk
                        && this.depto !== ''
                        && this.subdepto !== ''
                        && this.rol !== ''
                        && Array.isArray(this.dias) && this.dias.length > 0
                        && this.horas !== null && this.horas !== '';
                }
             }">

            <x-page-header :title="$colaborador ? 'Editar colaborador' : 'Nuevo colaborador'" subtitle="Datos, rol y disponibilidad" icon="users">
                <x-slot:actions>
                    <a href="{{ route('colaboradores') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                        <x-icon name="arrow-left" class="w-4 h-4" /> Volver
                    </a>
                </x-slot:actions>
            </x-page-header>

            <form wire:submit="save" class="rounded-2xl bg-white dark:bg-slate-900 shadow-sm dark:shadow-black/20 border border-slate-200/70 dark:border-slate-800 p-6 space-y-6">
                @include('livewire.colaboradores.partials.campos-formulario')

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" :disabled="!formularioCompleto"
                            class="rounded-xl bg-gradient-to-br from-blue-600 to-sky-600 px-5 py-2.5 text-sm font-medium text-white shadow-lg shadow-blue-500/30 hover:from-blue-700 hover:to-sky-700 active:scale-[0.98] transition disabled:opacity-60 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="save">{{ $colaborador ? 'Guardar cambios' : 'Crear colaborador' }}</span>
                        <span wire:loading wire:target="save">Guardando…</span>
                    </button>
                    <a href="{{ route('colaboradores') }}" wire:navigate class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endif
