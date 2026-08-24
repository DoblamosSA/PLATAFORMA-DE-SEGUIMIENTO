{{-- Foto --}}
<div class="flex items-center gap-4">
    @if ($foto)
        <img src="{{ $foto->temporaryUrl() }}" alt="Vista previa de la foto" class="h-16 w-16 rounded-full object-cover shrink-0">
    @else
        <x-avatar :usuario="$colaborador" :nombre="$colaborador ? null : '?'" size="h-16 w-16" text="text-lg" />
    @endif
    <div class="flex-1">
        <label for="foto" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Foto</label>
        <input id="foto" type="file" wire:model="foto" accept="image/*"
               class="w-full text-sm text-slate-600 dark:text-slate-300 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 dark:file:bg-blue-500/15 file:px-3 file:py-1.5 file:text-blue-700 dark:file:text-blue-400 file:text-sm">
        <div wire:loading wire:target="foto" class="text-xs text-slate-400 dark:text-slate-500 mt-1">Subiendo…</div>
        {{-- Span siempre presente (nunca @if/@error) para que Livewire parchee su
             texto en vez de agregar/quitar el nodo: agregar/quitar nodos desalinea
             el conteo de hermanos y hace que morphdom pierda el valor de otros
             campos del formulario en el mismo re-render (causa real del bug donde
             el formulario se veia vacio tras una validacion fallida). --}}
        <span class="text-xs text-rose-600 dark:text-rose-400 {{ $errors->has('foto') ? '' : 'hidden' }}">{{ $errors->first('foto') }}</span>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Nombre *</label>
        <input id="name" type="text" wire:model="name" x-on:input="name = $event.target.value" required
               class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        <span class="text-xs text-rose-600 dark:text-rose-400 {{ $errors->has('name') ? '' : 'hidden' }}">{{ $errors->first('name') }}</span>
    </div>
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Correo *</label>
        <input id="email" type="email" wire:model="email" x-on:input="email = $event.target.value" required
               class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        <span class="text-xs text-rose-600 dark:text-rose-400 {{ $errors->has('email') ? '' : 'hidden' }}">{{ $errors->first('email') }}</span>
    </div>
    <div>
        <label for="telefono" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Teléfono</label>
        <input id="telefono" type="text" wire:model="telefono"
               class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        <span class="text-xs text-rose-600 dark:text-rose-400 {{ $errors->has('telefono') ? '' : 'hidden' }}">{{ $errors->first('telefono') }}</span>
    </div>
    <div>
        <label for="cargo" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Cargo</label>
        <input id="cargo" type="text" wire:model="cargo"
               class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
    </div>
</div>

<x-toggle wire:model="activo" label="Colaborador activo" />

{{-- Contraseña: solo se captura aqui, nunca se muestra --}}
<div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/40 p-4 space-y-3">
    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
        Contraseña {{ $colaborador ? '(dejar en blanco para no cambiarla)' : '*' }}
    </p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="password" class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Nueva contraseña</label>
            <input id="password" type="password" wire:model="password" x-on:input="password = $event.target.value" autocomplete="new-password"
                   class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <span class="text-xs text-rose-600 dark:text-rose-400 {{ $errors->has('password') ? '' : 'hidden' }}">{{ $errors->first('password') }}</span>
        </div>
        <div>
            <label for="password_confirmation" class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Confirmar contraseña</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" x-on:input="passwordConfirmation = $event.target.value" autocomplete="new-password"
                   class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
    </div>
</div>

{{-- Departamento, subdepartamento (antes "Área") y rol. Cascada en el cliente
     (Alpine) con datos precargados: elegir el departamento filtra sus
     subdepartamentos y sus roles al instante, sin round-trips (fragiles dentro
     del modal). Los valores se sincronizan con Livewire via @entangle y viajan
     con el submit para validarse/guardarse.

     El wire:key de este bloque evita que Livewire recree (en vez de parchear)
     este x-data en cada re-render, que era la causa mas probable de que el
     formulario se viera "vacio" tras una validacion fallida. Se mantienen
     ademas los x-init de cada <select>/<input> (ahora aplicados de forma
     uniforme a los tres selects y a horasDiarias) como respaldo: sin
     verificacion manual en navegador con throttling de red que confirme que
     el wire:key por si solo es suficiente en todos los casos, es mas seguro
     conservar y uniformar este parche existente que retirarlo. --}}
<div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/40 p-4 space-y-3"
     wire:key="colaborador-depto-{{ $colaborador?->id ?? 'nuevo' }}"
     x-data="{
        depto: @entangle('department_id'),
        subdepto: @entangle('sub_department_id'),
        rol: @entangle('role_id'),
        cascada: @js($cascada),
        get subdepartamentos() { return this.cascada[this.depto]?.subdepartamentos ?? []; },
        get roles() { return this.cascada[this.depto]?.roles ?? []; },
        onDepto() {
            if (!this.subdepartamentos.some(s => s.id === this.subdepto)) this.subdepto = '';
            if (!this.roles.some(r => r.id === this.rol)) this.rol = '';
        }
     }">
    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Departamento, subdepartamento y rol</p>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label for="department_id" class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Departamento *</label>
            <select id="department_id" x-model="depto" @change="onDepto()"
                    x-init="$nextTick(() => $el.value = depto)"
                    class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Selecciona…</option>
                @foreach ($departamentos as $dpto)
                    <option value="{{ $dpto->id }}">{{ $dpto->nombre }}</option>
                @endforeach
            </select>
            <span class="text-xs text-rose-600 dark:text-rose-400 {{ $errors->has('department_id') ? '' : 'hidden' }}">{{ $errors->first('department_id') }}</span>
        </div>
        <div>
            <label for="sub_department_id" class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Subdepartamento *</label>
            <select id="sub_department_id" x-model="subdepto" :disabled="!depto"
                    x-init="$nextTick(() => $el.value = subdepto)"
                    class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed">
                <option value="" x-text="depto ? 'Selecciona…' : 'Elige un departamento primero'"></option>
                <template x-for="s in subdepartamentos" :key="s.id">
                    <option :value="s.id" x-text="s.nombre"></option>
                </template>
            </select>
            <span class="text-xs text-rose-600 dark:text-rose-400 {{ $errors->has('sub_department_id') ? '' : 'hidden' }}">{{ $errors->first('sub_department_id') }}</span>
        </div>
        <div>
            <label for="role_id" class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Rol en el departamento *</label>
            <select id="role_id" x-model="rol" :disabled="!depto"
                    x-init="$nextTick(() => $el.value = rol)"
                    class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed">
                <option value="" x-text="depto ? 'Selecciona…' : 'Elige un departamento primero'"></option>
                <template x-for="r in roles" :key="r.id">
                    <option :value="r.id" x-text="r.nombre"></option>
                </template>
            </select>
            <span class="text-xs text-rose-600 dark:text-rose-400 {{ $errors->has('role_id') ? '' : 'hidden' }}">{{ $errors->first('role_id') }}</span>
        </div>
    </div>
</div>

{{-- Disponibilidad. Los dias y las horas se manejan en Alpine (cliente) y se
     sincronizan con Livewire via @entangle: asi la seleccion y la capacidad
     responden al instante, sin ir y volver al servidor en cada clic (fragil
     cuando el formulario se monta dentro del modal). Los valores viajan con
     el submit para validarse/guardarse. --}}
<div class="rounded-xl border border-blue-100 dark:border-blue-500/30 bg-blue-50/40 dark:bg-blue-500/10 p-4 space-y-3"
     wire:key="colaborador-disponibilidad-{{ $colaborador?->id ?? 'nuevo' }}"
     x-data="{
        dias: @entangle('diasLaborales'),
        horas: @entangle('horasDiarias'),
        toggleDia(d) {
            this.dias = this.dias.includes(d) ? this.dias.filter(x => x !== d) : [...this.dias, d];
        },
        get capacidad() {
            return this.dias.length * (parseFloat(this.horas) || 0);
        },
        get capacidadTexto() {
            return parseFloat(this.capacidad.toFixed(2)) + ' h';
        }
     }">
    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Disponibilidad</p>

    <div>
        <span class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-2">Días laborales *</span>
        <div class="flex flex-wrap gap-2" role="group" aria-label="Días laborales">
            @foreach (\App\Models\User::DIAS as $dia)
                <button type="button" @click="toggleDia('{{ $dia }}')"
                        :aria-pressed="dias.includes('{{ $dia }}').toString()"
                        :class="dias.includes('{{ $dia }}')
                            ? 'bg-blue-600 border-blue-600 text-white'
                            : 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300'"
                        class="inline-flex items-center justify-center h-9 w-9 rounded-lg border text-sm font-semibold cursor-pointer transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                        aria-label="Día {{ $dia }}">
                    {{ $dia }}
                </button>
            @endforeach
        </div>
        <span class="block text-xs text-rose-600 dark:text-rose-400 mt-1 {{ $errors->has('diasLaborales') ? '' : 'hidden' }}">{{ $errors->first('diasLaborales') }}</span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
        <div>
            <label for="horasDiarias" class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Horas diarias * (máx. 12)</label>
            <input id="horasDiarias" type="number" step="0.5" min="0.5" max="12" x-model="horas"
                   x-init="$nextTick(() => $el.value = horas)"
                   class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <span class="text-xs text-rose-600 dark:text-rose-400 {{ $errors->has('horasDiarias') ? '' : 'hidden' }}">{{ $errors->first('horasDiarias') }}</span>
        </div>
        <div class="rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-3 py-2">
            <p class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">Capacidad semanal</p>
            <p class="text-lg font-bold text-blue-600 dark:text-blue-400 tabular-nums" x-text="capacidadTexto">0 h</p>
        </div>
    </div>
</div>
