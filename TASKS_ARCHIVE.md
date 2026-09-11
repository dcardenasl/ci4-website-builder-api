# TASKS_ARCHIVE — ci4-api-starter

> Historial de tareas completadas. Movido desde TASKS.md para mantener el tracker activo liviano.
> Última actualización: 2026-08-25

## ✅ CNV-007-F9 — Reconciliación de alcance — 2026-09-11

La autorización por recurso queda centralizada en Domain (`729aa89`). API/Hub sigue siendo dueño
de identidad, roles y permisos globales, pero no replica la ACL de contenido ni agrega un modelo
de tenant sin un requisito de producto. No hay implementación local pendiente.

---

## ✅ Backport de mejoras de Teatro Museo — Fase 0 y CVE (2026-08-25)

- **BACKPORT-00-api** — `ci4-api-core` 1.5.1, `LocalDriver` con visibilidad explícita y métricas
  de requests agregadas con `getDashboardStats()`; verificado con `composer quality`.
- **BACKPORT-CVE-api** — CI4 4.7.4 y Guzzle 7.15.5; guards de tipo compatibles con el stub de
  `getJSON()` y `composer audit` limpio; verificado con `composer quality`.

## ✅ Backport de mejoras de Teatro Museo — Fase 1 (2026-08-25)

- **BACKPORT-01-api** — DTOs con preservación de `null`, autorización centralizada de archivos,
  scanner fail-closed, refresh-token por familia con detección de reuso, `auth_token_version` y
  composición genérica de roles; verificado con `composer quality`.

## ✅ Backport de mejoras de Teatro Museo — Fase 2 y 5 (2026-08-25)

- **BACKPORT-02-api** — se verificó que el Hub expone la identidad M2M genérica (`hub.appCode`/
  `hub.apiKey`) consumida por el BFF opcional; Fase 5 documentó los defaults de runtime y contratos
  de seguridad del starter.

## ✅ Remediación de huecos profundos — GAP-01-api (2026-08-25)

- **GAP-01-api** — cerrados los 14 ítems aplicables de Fase 1; FTP quedó diferido explícitamente por no tener consumidor ni hosting asumido. Commits: `8e4f80b`, `debe58c`, `30dfaa2`, `fa2148c`, `3cc7b58`, `b245959`, `b1acabb`, `eb15dcc`, `922ccc5`, `04ca6c6`, `3d82887`, `1667314`, `cb16c25`, `84b4153`.

---

## ✅ Refactorización y hardening (2026-05-26)

| ID | Descripción | Estado |
|---|---|---|
| API-017 | Auth/IAM DTO typing. `AuthService` y `AuthServiceInterface` ahora exponen `LoginRequestDTO`, `RegisterRequestDTO`, `UpdateMeRequestDTO` y retornan `LoginResponseDTO`, `RegisterResponseDTO`, `MeResponseDTO` concretos. `SessionManager::generateSessionResponse()` devuelve `LoginResponseDTO` con `MeResponseDTO` anidado. `UserPermissionsService` deja de ensamblar `ApplicationSummary` como array y retorna el DTO directamente. `AuthServiceTest` y un test unitario nuevo para `LoginResponseDTO` cubren el contrato. Verificado con `composer quality`. | ✅ |
| AUDIT-001 | Audit Trail Reliability. `GET /health` degrada a `degraded` cuando el único problema es presión crítica de disco y la auditoría asíncrona está activa; se mantiene `unhealthy` para otros fallos. Añadido test unitario para la política de degradación. Verificado con `composer quality`. | ✅ |

## ✅ CORE v1.0 milestone — paquete consumido desde Packagist (2026-05-09)

| ID | Descripción | Estado |
|---|---|---|
| API-011 | Publicar `ci4-api-core` en Packagist + migrar de path repo a constraint Packagist. Cerrado por CORE-006 cross-repo: `dcardenasl/ci4-api-core` v0.4.0 publicado 2026-05-09, `composer.json` del api-starter ya consume desde Packagist. | ✅ |

---

## ✅ Enterprise hardening (Milestone B5–B11, 2026-05-07)

| ID | Descripción | Estado |
|---|---|---|
| B7.1 | `AssignableRolesService` extracción del controller anti-pattern | ✅ |
| B7.2 | Headers de deprecación + `/api/versions` + ADR-008 (EN+ES) | ✅ |
| B7.3 | `Idempotency-Key` opt-in + migración `idempotency_keys` + ADR-009 (EN+ES) | ✅ |
| B7.4 | RFC 7807 Problem Details opt-in + ADR-010 (EN+ES) | ✅ |
| B7.5 | Convención de paginación documentada en `docs/tech/pagination.md` (EN+ES) | ✅ |
| B9.2 | `GoogleLoginSoftDeletedUserTest` (2 tests, contrato de reactivación) | ✅ |
| B10.1 | `CorrelationIdFilter` + `RequestIdHolder` + propagación en ApiClient | ✅ |
| B11.1 | ADR-011 (multi-tenancy out-of-scope) + ADR-012 (config runtime mutability) EN+ES | ✅ |
| B11.2 | 4 runbooks (rotate JWT, failed migration, upgrade CI4, token-leak incident) EN+ES | ✅ |

---

## ✅ Endpoints de integración hub↔domain (2026-05-06/07)

| ID | Descripción | Estado |
|---|---|---|
| API-001 | `POST /api/v1/auth/introspect` — introspección JWT (RFC 7662-style). Filter `appKeyRequired`, `TokenIntrospectionService`, DTOs, doc OpenAPI. 8 feature tests. 603 tests verdes. | ✅ |
| API-002 | `POST /api/v1/auth/service-token` — M2M auth sin usuario. `ServiceTokenService`, `ApplicationPermissionsResolver`, `JwtService::encodeServiceToken()`, TTL configurable. 6 feature + 5 integration tests. 614 tests verdes. | ✅ |
| API-003 | Reglas de modificación de usuarios: `PATCH /api/v1/auth/me` (allowlist first_name/last_name/avatar_url). Email inmutable salvo superadmin. `assertNotSelf()` en PUT. | ✅ |
| API-005 | Bug: `JwtAuthFilter` crasheaba con service tokens (uid undefined). Null-safe + `PermissionFilter` distingue 401 vs 403. | ✅ |
| API-006 | `/auth/introspect` re-resuelve scope según `X-App-Key` del caller. `EffectivePermissionsResolver(uid, application_id)`. | ✅ |
| API-007 | `apps:bootstrap --create-api-key`: genera API key activa, output parseable `API_KEY=apk_...`. 4 integration tests. Desbloquea KICK-001. | ✅ |

---

## ✅ Deudas post-port + consumo ci4-api-core v0.2.0 (2026-05-07)

| ID | Descripción | Estado |
|---|---|---|
| API-015 | `HandlesTranslations` cascade delete — `afterDelete()` llama `TranslationModel::deleteForEntity()` dentro de la transacción de `BaseCrudService::destroy`. Integration test store/update/delete completo. | ✅ |
| API-016 | `GalleryService` → `PivotRepositoryInterface`. `PivotRepository` abstract, `findByIds` en `FileRepository`. Integration test end-to-end con fixture pivot table. Whitelist de `ServiceModelDependencyConventionsTest` reducida. | ✅ |
| API-017 | `DataBag` eliminado. `ResponseMapper::map()` acepta `object\|array` directamente (CORE-009). `HandlesTranslations::mapToResponse` pasa array directo. | ✅ |

**Refactor de consumo** (sin ID de tarea — trabajo derivado de ci4-api-core v0.2.0):
- Helpers procedurales consumidos desde `dcardenasl/ci4-api-core`
- Cadena de audit consumida desde core
- HTTP filters y logging stack consumidos desde core
- Mappers y utilidades de support consumidas desde core
- `BaseRepository` consumido desde core
- Exception handlers HTTP consumidos desde core
- `Filterable`, `Searchable`, `QueryBuilder` consumidos desde core
- Fixtures de tests actualizados a imports de `dcardenasl/ci4-api-core`
- `composer.lock` + swagger regenerados

---

*TASKS_ARCHIVE · ci4-api-starter · 2026-05-07*

---

## 📦 Migrado desde `TASKS.md` — 2026-07-21

- **PHPSTAN-01..08** — ampliación de paths, reducción del baseline a cero, correcciones de
  false-safety, anotaciones de tipos, generics, eliminación de dead code y suites unit/feature en
  verde.
- **IAM-001..003** — inferencia automática de `application_id`, auditoría de modelos y
  cumplimiento de `BaseAuditableModel`.
- **DTO-001..002** — auditoría de Services que usaban arrays y guardrail de análisis estático para
  evitar regresiones DTO-first.
- **CORE-001..004** — hardening de `RepositoryInterface` y `AuditServiceInterface`, boundary tipado
  de `ApiController`, implementación estricta en el starter y plantillas tipadas del scaffolder.

La orquestación Docker cross-repo permaneció abierta hasta el runner aislado y su E2E real de
`a68ae08`; la entrada histórica queda cerrada en el tracker activo.

## ✅ CNV-007-H1 y API-012 — 2026-09-11

- **CNV-007-H1 — `roles.ui_mode`.** Commit `c57b663`; migración `VARCHAR(10)`, default `full`,
  validación `full/simple`, DTOs, respuestas efectivas, OpenAPI y pruebas IAM.
- **API-012 — Docker out-of-the-box.** Commit `a68ae08`; workflow y `scripts/docker-e2e.sh` con
  proyecto/red/volúmenes aislados, migración, seed idempotente, probes, Swagger, restart y
  cleanup. E2E local ejecutado correctamente.

## ✅ CNV-007-F6 — Integración — 2026-09-11

El smoke real del editor consumió Hub/Domain sin regresiones; renovación de preview del Admin
respondió `200` con expiración y firma nuevas.
