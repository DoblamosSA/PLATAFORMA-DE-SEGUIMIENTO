---
id: application-surface
title: Rutas y superficie de la aplicación
tags: [rutas, livewire, pantallas, modulos]
fuentes:
  - routes/web.php
  - app/Livewire
updated: 2026-07-22
---

# Superficie de la aplicación

Todas las rutas siguientes requieren autenticación y aplican `NoCacheHeaders`.

| Módulo | Rutas principales | Componente |
| --- | --- | --- |
| Inicio | `/dashboard` | `Dashboard` |
| Proyectos | `/proyectos`, `/nuevo`, `/{project}`, `/{project}/editar`, `/{project}/tablero` | `Proyectos/*` |
| Tareas globales | `/tareas`, `/nueva`, `/{task}/editar` | `Tareas/*` |
| Informes | `/informes/cumplimiento` | `Informes/ReporteMensual` |
| Colaboradores | `/colaboradores`, `/nuevo`, `/{colaborador}/editar` | `Colaboradores/*` |
| Departamentos | `/departamentos`, `/nuevo`, `/{department}/editar` | `Organization/Departamentos/*` |
| Subdepartamentos | `/subdepartamentos`, `/nuevo`, `/{subDepartment}/editar` | `Organization/SubDepartamentos/*` |
| Roles | `/roles`, `/nuevo`, `/{role}/editar` | `Organization/Roles/*` |
| Permisos | `/permisos` | `Organization/Permisos/ListaPermisos` |

El módulo global de tareas está restringido con `can:admin`. La app también expone POST `/push/subscribe` y `/push/unsubscribe` para gestionar la suscripción Web Push del navegador activo.
