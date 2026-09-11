# TASKS — ci4-website-builder-api

> Trabajo abierto de este repositorio. Lo cerrado está en [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md).
> Plan cross-repo: [`../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md`](../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md).

## 🔴 En progreso

*(vacío)*

## 🟡 Próximo

*(vacío; la autorización por recurso es Domain-owned y no se duplica en API/Hub)*

## ✅ Cerrado con evidencia

- **CNV-007-H1 — `roles.ui_mode`.** Commit `c57b663`; migración portable, DTOs, validación,
  respuestas efectivas por roles, OpenAPI y regresiones IAM.
- **API-012 — Docker out-of-the-box.** Commit `a68ae08`; runner aislado y workflow CI cubren build,
  migración, bootstrap idempotente, `/ping`, `/ready`, Swagger, reinicio y cleanup. E2E local
  ejecutado con el daemon Docker.
- **CNV-007-F6 — Integración.** El smoke del editor consumió el Hub/Domain existentes sin romper
  sesión ni contratos API; renovación Admin `POST /admin/cms/editor/pages/1/preview/renew` respondió
  `200` con firma y expiración nuevas.
- **CNV-007-F9 — Reconciliación de alcance.** API/Hub conserva identidad, roles y permisos
  globales; Domain resuelve el alcance concreto de pages, entries y collections. No se duplica ACL
  ni se introduce multi-tenancy física. Evidencia Domain: `729aa89`.

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
