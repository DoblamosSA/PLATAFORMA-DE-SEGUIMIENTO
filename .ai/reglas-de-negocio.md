---
id: business-rules
title: Reglas de negocio, SLA y métricas
tags: [reglas, sla, metricas, capacidad, semaforo]
fuentes:
  - app/Models/Project.php
  - app/Models/Task.php
  - app/Models/SlaPolicy.php
  - app/Services/MetricasService.php
  - app/Services/CapacidadService.php
updated: 2026-07-29
---

# Reglas de negocio

## SLA de tareas

Una tarea abierta está vencida si no está `completada` ni `cancelada`, tiene `fecha_limite` y dicha fecha ya pasó. Las políticas SLA activas se seleccionan por tipo y prioridad. Si no existe una política, la duración predeterminada es: crítica 4 h, alta 24 h, media 72 h y baja 120 h.

## Cumplimiento y progreso

El cumplimiento del proyecto considera solo tareas completadas: `a_tiempo / completadas * 100`. Si no hay completadas, es 0. El progreso considera tareas completadas sobre el total no cancelado; si no hay tareas aplicables, es 0.

## Semáforo del proyecto

El semáforo no depende directamente del estado o fechas del proyecto, sino de sus tareas. Un proyecto cancelado no tiene semáforo. Si ninguna tarea se ha ejecutado, es `planeado`, incluso si hay pendientes vencidas. Una vez iniciado: hay abiertas vencidas → `vencido`; hay abiertas que vencen en máximo dos días → `en_riesgo`; en otro caso → `saludable`.

## Capacidad operativa

La capacidad semanal de una persona es la cantidad de días laborales configurados multiplicada por sus horas diarias. Los códigos de día son `L`, `M`, `X`, `J`, `V`, `S`, `D`; su traducción a días de Carbon está definida en `User::DIAS_CARBON`.

La **carga operativa de una semana** suma las `horas_estimadas` de tareas cuyo `fecha_asignacion` cae en esa semana calendario (lunes–domingo, `APP_TIMEZONE`). Incluye completadas/certificadas; excluye canceladas. Completar una tarea **no libera** capacidad en esa misma semana. Al iniciar una nueva semana, solo cuentan las asignaciones de la semana nueva.

El inicio y fin planificados (`inicio_planificado`, `fecha_limite`) se calculan con `HorarioLaboralService` respetando la jornada (`config/operativa.php` + perfil del colaborador), sin asumir horas corridas de reloj.

No se permite asignar trabajo nuevo si el colaborador tiene tareas abiertas asignadas en semanas anteriores (`CierreSemanalService`).

El módulo **Evaluador de colaboradores** (`EvaluadorRendimientoService`) calcula puntaje 0–100% con pesos configurables (puntualidad, sin vencidas, carga completada), clasificación Crítico/Medio/Excelente y ranking con desempate.