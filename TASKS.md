# TASKS — ci4-website-builder-api

> Fuente de verdad para trabajo abierto en este repositorio.
> Los entregables cerrados están en [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md).
> Seguimiento cross-repo: [`../TASKS.md`](../TASKS.md).
> Tracker depurado el 2026-07-21; no se conservan notas de conversación ni bitácoras de participantes.

## 🔴 En progreso

### Remediación de huecos profundos (parte Hub/API)

> Plan completo: [`../docs/plans/2026-08-25-plan-remediacion-huecos-profundos.md`](../docs/plans/2026-08-25-plan-remediacion-huecos-profundos.md).
> Auditoría origen: [`../docs/audits/2026-08-25-auditoria-profunda-backport-git-history.md`](../docs/audits/2026-08-25-auditoria-profunda-backport-git-history.md).
> Tracker cross-repo: [`../TASKS.md`](../TASKS.md).

## 🟡 Próximo

### CNV-007 — Editor visual (canvas): port del hub

> Verificación de arranque en
> [`../ci4-website-suite/docs/plan/2026-09-09-editor-visual-canvas-port-f7.md`](../ci4-website-suite/docs/plan/2026-09-09-editor-visual-canvas-port-f7.md).

- [ ] **CNV-007-H1 — `roles.ui_mode`.** Columna `VARCHAR(10)` con default `full` (no ENUM, por
      portabilidad), en `allowedFields`, DTOs de rol y validación de dominio. La tabla tiene la misma
      forma que la de la suite y ya recibió una columna por migración, así que adapta directo.

## ⚪ Backlog

- [ ] **API-012 — Docker out-of-the-box:** validar la orquestación cross-repo en `ci4-kickstart`
  después de la idempotencia de `docker/entrypoint.sh`.
  **Estado al 2026-09-01:** el API falla ante errores de migración/seeder y Compose acepta
  nombres aislados para E2E; Kickstart ya comprueba `/ping`, Swagger, migraciones, bootstrap,
  reinicio y limpieza. Falta ejecutar el flujo con un daemon Docker real en CI.

## ⚠️ Señales de activación

- **API-014 — Multi-tenant nativo:** fuera de alcance mientras no exista una señal real que exija
  aislamiento físico o un SLA propio.
- **SEÑAL-API-001 — `InvalidChars` con enteros en JSON:** mantener el workaround documentado hasta
  que exista un segundo endpoint afectado o una corrección upstream de CI4.
- **FILES-001 — Endpoints de archivos faltantes:** crear tareas individuales cuando se prioricen
  `PATCH /files/{id}`, replace, regeneración de variantes o consulta de usages.

## 🏗️ Contratos de arquitectura

- **DTO-First:** toda entrada y salida de Controller usa DTOs; no arrays raw sin contrato.
- **Services puros:** no conocen HTTP ni `$request`.
- **Controllers delgados:** usar `handleRequest()` de `ApiController`.
- **Permisos:** usar separador `.`; nunca `:`.
- **Rutas:** organizar endpoints en `app/Config/Routes/v1/<dominio>.php`.
- **Tests:** todo endpoint nuevo necesita al menos un Feature test.
- **CRUD nuevo:** preferir `php spark make:crud {Resource} --domain {Domain} --route {slug}`.
- **OpenAPI:** regenerar Swagger al cerrar cambios de endpoints.
- **Migraciones:** nunca modificar migraciones existentes; crear una nueva para cada cambio de schema.

## 🔧 Referencias

- Histórico: [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md)
- Tracker global: [`../TASKS.md`](../TASKS.md)
