@props(['tipo' => 'estado', 'valor' => ''])

@php
    $mapas = [
        'estado' => [
            'pendiente'   => ['Pendiente', 'bg-gray-100 text-gray-700'],
            'en_progreso' => ['En progreso', 'bg-blue-100 text-blue-700'],
            'en_revision' => ['En revision', 'bg-purple-100 text-purple-700'],
            'completada'  => ['Completada', 'bg-emerald-100 text-emerald-700'],
            'cancelada'   => ['Cancelada', 'bg-gray-100 text-gray-400 line-through'],
            'planeado'    => ['Planeado', 'bg-gray-100 text-gray-700'],
            'en_pausa'    => ['En pausa', 'bg-amber-100 text-amber-700'],
            'completado'  => ['Completado', 'bg-emerald-100 text-emerald-700'],
            'cancelado'   => ['Cancelado', 'bg-gray-100 text-gray-400 line-through'],
        ],
        'prioridad' => [
            'baja'    => ['Baja', 'bg-gray-100 text-gray-600'],
            'media'   => ['Media', 'bg-sky-100 text-sky-700'],
            'alta'    => ['Alta', 'bg-orange-100 text-orange-700'],
            'critica' => ['Critica', 'bg-rose-100 text-rose-700'],
        ],
        'tipo' => [
            'software'        => ['Software', 'bg-indigo-100 text-indigo-700'],
            'soporte'         => ['Soporte', 'bg-teal-100 text-teal-700'],
            'infraestructura' => ['Infraestructura', 'bg-cyan-100 text-cyan-700'],
        ],
    ];

    [$texto, $clases] = $mapas[$tipo][$valor] ?? [ucfirst($valor), 'bg-gray-100 text-gray-600'];
@endphp

<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $clases }}">
    {{ $texto }}
</span>
