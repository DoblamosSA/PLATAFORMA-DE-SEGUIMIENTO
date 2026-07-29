<?php

/**
 * Configuracion centralizada de la operativa semanal (carga, horario y puntaje).
 *
 * Supuestos documentados (no estaban fijados en .ai/):
 * - Semana calendario = lunes 00:00 → domingo 23:59:59 en APP_TIMEZONE.
 * - Jornada diaria inicia a las hora_inicio_jornada (por defecto 08:00) y
 *   dura User.horas_diarias en dias de User.dias_laborales.
 * - La carga de una semana cuenta tareas con fecha_asignacion en esa semana
 *   (excluye canceladas); completadas/certificadas siguen contando.
 * - No existe entidad "sede"; el evaluador filtra por departamento.
 */
return [

    'hora_inicio_jornada' => (int) env('OPERATIVA_HORA_INICIO', 8),

    'minuto_inicio_jornada' => (int) env('OPERATIVA_MINUTO_INICIO', 0),

    /**
     * Pesos del puntaje semanal (deben sumar 1.0).
     * - puntualidad: % tareas finalizadas a tiempo sobre asignadas.
     * - sin_vencidas: 100% menos proporción de tareas vencidas abiertas.
     * - carga_completada: % horas de tareas completadas / horas asignadas.
     */
    'puntaje' => [
        'peso_puntualidad' => 0.50,
        'peso_sin_vencidas' => 0.30,
        'peso_carga_completada' => 0.20,
    ],

    /** Umbrales de clasificacion: [min_inclusivo, etiqueta]. */
    'clasificacion' => [
        ['min' => 90, 'clave' => 'excelente', 'etiqueta' => 'Excelente', 'color' => 'emerald'],
        ['min' => 50, 'clave' => 'medio', 'etiqueta' => 'Medio', 'color' => 'amber'],
        ['min' => 0, 'clave' => 'critico', 'etiqueta' => 'Crítico', 'color' => 'rose'],
    ],
];
