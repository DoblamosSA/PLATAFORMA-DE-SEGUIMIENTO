---
id: access-rbac
title: Acceso, visibilidad y RBAC
tags: [autorizacion, roles, permisos, visibilidad]
fuentes:
  - app/Models/User.php
  - app/Models/Project.php
  - app/Models/Task.php
  - app/Domain/Organization/Concerns/HasOrganizationAccess.php
  - app/Domain/Organization/Services/PermissionResolutionService.php
updated: 2026-07-22
---

# Acceso y RBAC

## Roles funcionales legados

Los valores de `users.rol` son `admin`, `lider`, `tecnico` y `evaluador`. Admin y líder se consideran coordinadores. Este mecanismo controla la mayor parte de las reglas actuales de proyectos y tareas.

- Solo admin crea/edita desde el módulo global de tareas y puede eliminar subtareas o comentarios.
- Admin y coordinador pueden crear proyectos.
- Admin y evaluador pueden rechazar una tarea completada.
- Todo usuario con acceso al proyecto puede crear subtareas y comentarios.

## Visibilidad de información

`Project::visiblesPara(user)` devuelve todo para admin; para los demás, solo proyectos donde son responsables o integrantes del equipo. `Task::visiblesPara(user)` devuelve todo para coordinador; los demás ven tareas propias, de proyectos donde participan o de proyectos que lideran.

Para gestionar un tablero, mover tarjetas o comentar, el usuario debe ser admin, responsable del proyecto o integrante de su equipo (`Project::usuarioPuedeGestionar`).

## RBAC organizacional

El trait `HasOrganizationAccess` añade departamentos, subdepartamentos, roles globales y `hasPermission(slug)` a `User`. `esSuperAdmin()` reconoce tanto el rol legado `admin` como un rol global `super-admin`.

Los roles primarios son globales y no eliminables. Un rol de departamento puede heredar de un padre. Los permisos de un rol pueden otorgarse (`grant`) o revocarse (`deny`); el servicio de resolución calcula el conjunto efectivo. No asumir que este RBAC ya sustituye todas las comprobaciones de `users.rol`.
