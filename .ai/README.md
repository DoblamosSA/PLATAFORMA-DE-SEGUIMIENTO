---
id: ai-context-index
title: Índice de contexto del proyecto
tags: [rag, contexto, arquitectura]
source_of_truth: codebase
updated: 2026-07-22
---

# Contexto RAG: Plataforma de Seguimiento

Esta carpeta es la base de conocimiento de la aplicación para asistentes de IA. Cada archivo está organizado por un único tema, con metadatos YAML y secciones autocontenidas para favorecer la recuperación semántica (RAG).

## Cómo recuperar contexto

1. Busca primero en este índice por intención o entidad.
2. Recupera el documento temático y la sección más específica; evita cargar todos los documentos.
3. Trata el código y las migraciones referenciadas como fuente de verdad si hay discrepancias.
4. Al cambiar comportamiento, actualiza el documento relacionado y su fecha `updated`.

## Mapa de consultas

| Necesidad | Documento |
| --- | --- |
| Visión general, capas y stack | [arquitectura.md](arquitectura.md) |
| Tablas, atributos y relaciones | [entidades.md](entidades.md) |
| Roles, permisos y alcance de datos | [acceso-y-rbac.md](acceso-y-rbac.md) |
| Casos de uso y transiciones | [flujos.md](flujos.md) |
| SLA, progreso, capacidad y reglas | [reglas-de-negocio.md](reglas-de-negocio.md) |
| Rutas, pantallas y componentes | [superficie-aplicacion.md](superficie-aplicacion.md) |
| Vocabulario de dominio | [glosario.md](glosario.md) |

## Convenciones de mantenimiento

- Mantener los documentos en español, igual que el dominio y la interfaz.
- Incluir rutas de archivos reales en `fuentes` para poder validar cada afirmación.
- Preferir secciones de 100 a 300 palabras y títulos descriptivos: son las unidades de recuperación.
- No incluir secretos, valores de `.env`, datos personales ni copias completas de código.
