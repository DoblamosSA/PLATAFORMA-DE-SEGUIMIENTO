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
updated: 2026-07-22
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
