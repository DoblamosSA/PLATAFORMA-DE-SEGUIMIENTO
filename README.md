<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo"></a></p>

# Plataforma de Seguimiento

> Seguimiento de proyectos, tareas, SLA y capacidad operativa, con organización y RBAC.

Aplicación web interna (Laravel 11 + Livewire 3) para dar seguimiento a proyectos de tecnología (software, soporte e infraestructura), sus tareas y subtareas, cumplimiento de SLA (acuerdo de nivel de servicio), capacidad operativa de los colaboradores y la estructura organizacional (departamentos, roles y permisos).

## Inicio rápido

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
composer run dev
```

`composer run dev` levanta en paralelo el servidor (`artisan serve`), la cola, los logs (`artisan pail`) y Vite. Ver [Primeros pasos](docs/primeros-pasos.md) para el detalle.

## Funcionalidades principales

- **Proyectos y tablero Kanban** — columnas configurables por proyecto que traducen la posición visual a un estado canónico de tarea.
- **Tareas con SLA y semáforo** — vencimiento por tipo/prioridad y estado visual (`planeado`, `saludable`, `en_riesgo`, `vencido`).
- **Capacidad operativa** — cálculo semanal por colaborador (días laborales × horas diarias) y evaluación de rendimiento con ranking.
- **Informes** — cumplimiento mensual y evaluación de colaboradores.
- **Organización y RBAC** (control de acceso basado en roles) — departamentos, subdepartamentos, roles jerárquicos y permisos (`grant`/`deny`), como subsistema independiente.
- **Notificaciones Web Push** — VAPID + service worker para cambios de tareas/subtareas.

## Documentación

| Guía | Descripción |
|------|-------------|
| [Primeros pasos](docs/primeros-pasos.md) | Instalación, configuración inicial y primer arranque |
| [Arquitectura](docs/arquitectura.md) | Patrón de arquitectura, capas del código y límites entre dominios |
| [Configuración](docs/configuracion.md) | Variables de entorno y configuración de la jornada operativa |
| [Notificaciones Push](docs/notificaciones-push.md) | Claves VAPID, service worker y particularidades por plataforma |
| [Seguridad y permisos](docs/seguridad-y-permisos.md) | Roles legados, RBAC organizacional y reglas de visibilidad |
| [Despliegue](deploy/README.md) | Despliegue en producción vía Docker y GitHub Actions |
| [Base de conocimiento de dominio](.ai/README.md) | Entidades, flujos y reglas de negocio en detalle, para asistentes de IA |

## Uso

Proyecto interno de uso privado; no está publicado como software de código abierto.
