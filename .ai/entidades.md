---
id: domain-entities
title: Entidades y relaciones del dominio
tags: [modelo-datos, proyectos, tareas, usuarios, organizacion]
fuentes:
  - app/Models
  - app/Domain/Organization/Models
  - database/migrations
updated: 2026-07-22
---

# Entidades del dominio

## Proyectos y tablero

`Project` representa un proyecto de tipo `software`, `soporte` o `infraestructura`. Guarda responsable, fechas, prioridad, estado y un progreso calculado. Tiene muchas `Task`, muchas `BoardColumn` y muchos integrantes `User` mediante `project_user` (con `rol_en_proyecto`).

`BoardColumn` pertenece a un proyecto y contiene `nombre`, `estado`, `posicion` y `color`. Una columna representa un estado de tarea; no es solo decoración visual.

## Tareas y trazabilidad

`Task` puede pertenecer opcionalmente a un proyecto (permite actividades de soporte sueltas). Tiene asignado, creador, tipo, prioridad, estado, fechas SLA, columna Kanban, posición y estimación de horas. Se relaciona con muchas `Subtask` y `TaskActivity`.

`Subtask` desglosa una tarea en `titulo` y `horas`; su suma alimenta `tasks.horas_estimadas`. `TaskActivity` registra acciones y comentarios asociados a una tarea. `AuditLog` registra trazabilidad de entidades generales que no necesariamente tienen tarea.

## Personas y configuración de SLA

`User` es el usuario autenticable. Además de identidad, conserva el rol legado, perfil operativo (`dias_laborales`, `horas_diarias`), tareas asignadas, proyectos bajo su responsabilidad y proyectos donde integra el equipo.

`SlaPolicy` configura `horas_resolucion` por la combinación de tipo y prioridad, con bandera `activo`. Una tarea guarda una instantánea de las horas aplicadas en `sla_horas`.

## Organización y RBAC

`Department` agrupa usuarios, subdepartamentos y roles propios; usa borrado lógico. `SubDepartment` pertenece a un departamento y se asocia a usuarios. `Role` puede heredar de otro rol y puede ser global o específico de un departamento. `Permission` es un catálogo con slugs como `projects.view`.

| Relación | Tabla pivote | Datos extra |
| --- | --- | --- |
| Proyecto ↔ Usuario | `project_user` | `rol_en_proyecto` |
| Departamento ↔ Usuario | `department_user` | `role_id`, `es_principal` |
| Subdepartamento ↔ Usuario | `sub_department_user` | — |
| Rol ↔ Permiso | `role_permissions` | `tipo`: `grant` o `deny` |
| Usuario ↔ Rol global | `user_roles` | — |
