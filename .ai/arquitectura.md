---
id: architecture-overview
title: Arquitectura de la Plataforma de Seguimiento
tags: [laravel, livewire, arquitectura, kanban, sla]
fuentes:
  - composer.json
  - routes/web.php
  - app/Domain/Organization
  - app/Services
updated: 2026-07-22
---

# Arquitectura

## Propósito

Plataforma web interna para dar seguimiento a proyectos de tecnología, tareas, cumplimiento de SLA, capacidad operativa y estructura organizacional. Los dominios principales son proyectos, tareas, colaboradores y organización/RBAC.

## Stack y ejecución

- Backend: PHP 8.2 y Laravel 11.
- UI reactiva del servidor: Livewire 3 y Volt; vistas Blade con Tailwind y Vite.
- Persistencia: Eloquent y migraciones de Laravel.
- Notificaciones: Web Push mediante `minishlink/web-push`.
- Pruebas: PHPUnit, separadas en `tests/Feature` y `tests/Unit`.

## Capas del código

| Capa | Ubicación | Responsabilidad |
| --- | --- | --- |
| Presentación | `resources/views`, `app/Livewire` | Pantallas, formularios y acciones del usuario. |
| Dominio base | `app/Models` | Proyectos, tareas, SLA, auditoría y relaciones. |
| Organización | `app/Domain/Organization` | Modelos, DTO, repositorios, servicios y políticas RBAC. |
| Servicios | `app/Services` | Métricas, capacidad y Web Push. |
| Persistencia | `database/migrations`, `database/seeders` | Esquema, roles y permisos iniciales. |

## Límites importantes

- El RBAC organizacional es un subsistema independiente: departamentos, subdepartamentos, roles y permisos todavía no se relacionan directamente con `Project` ni `Task`.
- El campo legado `users.rol` controla varias autorizaciones funcionales; el sistema nuevo de permisos se consulta con `hasPermission()`.
- El tablero Kanban es por proyecto y sus columnas traducen la posición visual a un estado canónico de tarea.

## Puntos de entrada

Las rutas web están protegidas por autenticación y el middleware `NoCacheHeaders`. Las pantallas se resuelven como componentes Livewire. Consulte `superficie-aplicacion.md` para el inventario de rutas.
