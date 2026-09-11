# TASKS — ci4-website-builder-api

> Trabajo abierto de este repositorio. Lo cerrado está en [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md).
> Plan cross-repo: [`../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md`](../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md).

## 🔴 En progreso

*(vacío)*

## 🟡 Próximo

- [ ] **CNV-007-H1 — `roles.ui_mode`.** Migración nueva `VARCHAR(10)` con default `full`,
      `allowedFields`, DTOs, validación, endpoint, OpenAPI y regresiones. No usar ENUM para conservar
      portabilidad.
- [ ] **API-012 — Docker out-of-the-box.** Ejecutar E2E real en CI/daemon Docker: migraciones,
      seed/bootstrap, `/ping`, Swagger, reinicio idempotente y limpieza.
- [ ] **CNV-007-F6 — Integración.** Acompañar el smoke Panel ↔ Web y validar que H1 no rompe filtros,
      sesiones ni contratos existentes.
- [ ] **CNV-007-F9 — Autorización por recurso.** Solo en la fase final cross-repo.

## ⚠️ Señales de activación

- **API-014:** multi-tenant nativo solo con una señal real de aislamiento o SLA propio.
- **SEÑAL-API-001:** mantener workaround documentado para `InvalidChars` hasta un segundo endpoint
  afectado o corrección upstream.
- **FILES-001:** desglosar endpoints de archivos únicamente cuando se prioricen.

## 🏗️ Contratos

- DTO-first, Services sin HTTP, Controllers delgados y permisos con `.`.
- Swagger se regenera con cada cambio de endpoint; cada schema cambia mediante migración nueva.
- Todo endpoint nuevo necesita Feature test y calidad completa.

## 🔧 Referencias

- Plan: [`../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md`](../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md)
- Histórico: [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md)
