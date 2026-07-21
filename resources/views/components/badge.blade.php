@props(['tipo' => 'estado', 'valor' => ''])

@php
    $mapas = [
        'estado' => [
            'pendiente'   => ['Pendiente', 'bg-gray-100 text-gray-700 dark:bg-slate-500/15 dark:text-slate-300'],
            'en_progreso' => ['En progreso', 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300'],
            'en_revision' => ['En revision', 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300'],
            'completada'  => ['Completada', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'],
            'cancelada'   => ['Cancelada', 'bg-gray-100 text-gray-400 line-through dark:bg-slate-500/10 dark:text-slate-500'],
            'rechazada'   => ['Rechazada', 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300'],
            'planeado'    => ['Planeado', 'bg-gray-100 text-gray-700 dark:bg-slate-500/15 dark:text-slate-300'],
            'en_pausa'    => ['En pausa', 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'],
            'completado'  => ['Completado', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'],
            'cancelado'   => ['Cancelado', 'bg-gray-100 text-gray-400 line-through dark:bg-slate-500/10 dark:text-slate-500'],
        ],
        'prioridad' => [
            'baja'    => ['Baja', 'bg-gray-100 text-gray-600 dark:bg-slate-500/15 dark:text-slate-300'],
            'media'   => ['Media', 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300'],
            'alta'    => ['Alta', 'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300'],
            'critica' => ['Critica', 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300'],
        ],
        'tipo' => [
            'software'        => ['Software', 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300'],
            'soporte'         => ['Soporte', 'bg-teal-100 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300'],
            'infraestructura' => ['Infraestructura', 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300'],
        ],
        'rol' => [
            'admin'     => ['Administrador', 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300'],
            'lider'     => ['Coordinador', 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300'],
            'tecnico'   => ['Colaborador', 'bg-teal-100 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300'],
            'evaluador' => ['Evaluador', 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'],
        ],
        'carga' => [
            'disponible' => ['Disponible', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'],
            'alta'       => ['Carga alta', 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'],
            'al_limite'  => ['Al límite', 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300'],
        ],
    ];

    [$texto, $clases] = $mapas[$tipo][$valor] ?? [ucfirst($valor), 'bg-gray-100 text-gray-600 dark:bg-slate-500/15 dark:text-slate-300'];
@endphp

<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $clases }}">
    {{ $texto }}
</span>
