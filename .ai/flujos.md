---
id: business-flows
title: Flujos operativos y transiciones
tags: [flujos, tareas, kanban, sla, proyectos]
fuentes:
  - app/Models/Project.php
  - app/Models/Task.php
  - app/Observers/TaskObserver.php
  - app/Observers/SubtaskObserver.php
  - app/Livewire
updated: 2026-07-22
---

# Flujos operativos

## Crear y administrar un proyecto

Un admin o coordinador crea el proyecto, define responsable y equipo. El proyecto puede tener estado `planeado`, `en_progreso`, `en_pausa`, `completado` o `cancelado`. Al abrir o usar el tablero se garantizan columnas por defecto si aún no existen: Pendiente, En ejecución, Terminada y Certificada.

## Crear y asignar una tarea

La tarea recibe tipo, prioridad, asignado y fechas. Al aplicarse SLA, se busca la política activa por tipo/prioridad y se calcula `fecha_limite` desde `fecha_asignacion` (o el momento actual). Si no hay política, se usa una duración de respaldo por prioridad.

## Movimiento Kanban y estados

Cada columna tiene un estado canónico. Al mover una tarjeta, su `board_column_id` y posición cambian, y la tarea adopta el estado asociado. Los estados principales son `pendiente`, `en_progreso`, `en_revision`, `completada` y `cancelada`; el modelo también puede registrar `rechazada` tras una evaluación.

## Cierre y rechazo

Al completar, se fija `fecha_completada` y `cumplida_a_tiempo` comparando contra `fecha_limite`. Solo admin o evaluador puede rechazar una tarea completada: se limpia el cierre, se restablece el cumplimiento a `null` y queda en `rechazada`.

## Subtareas y estimación

Al crear, editar o eliminar una subtarea, los observadores recalculan `Task.horas_estimadas` como suma de sus horas. Si ya no hay horas, la estimación queda en `null`.

## Progreso y seguimiento

El progreso del proyecto se recalcula como porcentaje de tareas completadas sobre tareas no canceladas. Las actividades y comentarios de una tarea se guardan en `TaskActivity`; eventos generales se registran en `AuditLog`.
