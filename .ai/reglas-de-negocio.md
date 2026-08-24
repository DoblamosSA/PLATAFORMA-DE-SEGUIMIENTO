---
id: business-rules
title: Reglas de negocio, cumplimiento y métricas
tags: [reglas, cumplimiento, metricas, capacidad, semaforo]
fuentes:
  - app/Models/Project.php
  - app/Models/Task.php
  - app/Services/MetricasService.php
  - app/Services/CapacidadService.php
updated: 2026-08-24
---

# Reglas de negocio

## Fechas y cumplimiento de tareas

`fecha_inicio` y `fecha_limite` son obligatorias y se ingresan siempre a mano en el formulario de tareas (creación y edición, para cualquier usuario con permiso); ya no existe SLA ni auto-cálculo por horario laboral. Al editar `fecha_limite` de una tarea ya cerrada (`completada`/`cancelada`) se exige dejar una observación justificando el cambio.

Una tarea abierta está vencida si no está `completada` ni `cancelada`, tiene `fecha_limite` y dicha fecha ya pasó. El cumplimiento (`cumplida_a_tiempo`) se fija al completar la tarea comparando directamente `fecha_completada` contra `fecha_limite`: si la supera, queda en falta (`false`); si no, cumple (`true`).

## Cumplimiento y progreso

El cumplimiento del proyecto considera solo tareas completadas: `a_tiempo / completadas * 100`. Si no hay completadas, es 0. El progreso considera tareas completadas sobre el total no cancelado; si no hay tareas aplicables, es 0.

## Semáforo del proyecto

El semáforo no depende directamente del estado o fechas del proyecto, sino de sus tareas. Un proyecto cancelado no tiene semáforo. Si ninguna tarea se ha ejecutado, es `planeado`, incluso si hay pendientes vencidas. Una vez iniciado: hay abiertas vencidas → `vencido`; hay abiertas que vencen en máximo dos días → `en_riesgo`; en otro caso → `saludable`.

## Capacidad operativa

La capacidad semanal de una persona es la cantidad de días laborales configurados multiplicada por sus horas diarias. Los códigos de día son `L`, `M`, `X`, `J`, `V`, `S`, `D`; su traducción a días de Carbon está definida en `User::DIAS_CARBON`.

La **carga operativa de una semana** suma las `horas_estimadas` de tareas **abiertas** (`pendiente`, `en_progreso`, `en_revision`, `rechazada`) cuyo `fecha_asignacion` cae en esa semana calendario (lunes–domingo, `APP_TIMEZONE`). Completar o cancelar una tarea libera su cupo de inmediato, sin importar en qué semana se asignó. Al iniciar una nueva semana, solo cuentan las asignaciones de la semana nueva.

No se permite asignar trabajo nuevo si el colaborador tiene tareas abiertas asignadas en semanas anteriores (`CierreSemanalService`).

El módulo **Evaluador de colaboradores** (`EvaluadorRendimientoService`) calcula puntaje 0–100% con pesos configurables (puntualidad, sin vencidas, carga completada), clasificación Crítico/Medio/Excelente y ranking con desempate.