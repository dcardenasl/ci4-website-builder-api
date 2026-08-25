# TASKS — ci4-website-builder-api

> Fuente de verdad para trabajo abierto en este repositorio.
> Los entregables cerrados están en [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md).
> Seguimiento cross-repo: [`../TASKS.md`](../TASKS.md).
> Tracker depurado el 2026-07-21; no se conservan notas de conversación ni bitácoras de participantes.

## 🔴 En progreso

### Backport de mejoras de Teatro Museo (parte Hub/API)

> Plan completo (contexto, decisiones de alcance, todas las fases, todos los repos):
> [`../docs/plans/2026-08-24-plan-backport-teatromuseo.md`](../docs/plans/2026-08-24-plan-backport-teatromuseo.md).
> Tracker cross-repo: [`../TASKS.md`](../TASKS.md).

- [x] **BACKPORT-00-api — Fase 0:** bump `dcardenasl/ci4-api-core` v1.0.1 → v1.5.1 (fix de
      compatibilidad en `FakeApiKeyRepository::findAll()`), `LocalDriver` con
      `PortableVisibilityConverter` explícito, `RequestLogModel::getStats()` consolidado a queries
      de agregación + nuevo `getDashboardStats()`. Código y tests verificados en verde;
      **pendiente de commit**. Ver plan §Fase 0.
- [x] **BACKPORT-CVE-api:** bump `codeigniter4/framework` → v4.7.4 y `guzzlehttp/guzzle` → 7.15.5
      (CVEs críticos/altos de SQLi, path traversal y noncanonical host/cookie). Verificado
      (ninguno de los comportamientos con CVE se ejercita en este código); pendiente de commit.
      Ver plan §Remediación de CVEs.
- [ ] **BACKPORT-01-api — Fase 1:** auditoría completa de `array_filter($v !== null)` en todos los
      `*RequestDTO`; SEC-01 (`FilePolicyService` centralizado, permiso `files.admin`,
      `VirusScannerServiceInterface` fail-closed); SEC-02 (rotación de refresh-token por familia
      con detección de reuso, `auth_token_version`). Ver plan §Fase 1 — es la pieza de mayor valor
      de seguridad de todo el plan.

## 🟡 Próximo

### Backport de mejoras de Teatro Museo — fases posteriores (parte Hub/API)

- [ ] **BACKPORT-02-api — Fase 2:** el Hub expone la identidad M2M (`hub.appCode`/`hub.apiKey`) que
      consumirá el nuevo BFF opcional. Ver plan §Fase 2.

## ⚪ Backlog

- [ ] **API-012 — Docker out-of-the-box:** validar la orquestación cross-repo en `ci4-kickstart`
  después de la idempotencia de `docker/entrypoint.sh`.

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
