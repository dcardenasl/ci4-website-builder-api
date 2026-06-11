# TASKS — ci4-api-starter

> Fuente de verdad para trabajo en este repo.
> Historial de completadas: ver `TASKS_ARCHIVE.md`.
> Cross-repo: ver `../TASKS.md`.
> Última actualización: 2026-05-26 (tracker activo limpio; ver TASKS_ARCHIVE.md)

---

## 🔴 En progreso

*(vacío)*

---

## 🟡 Próximo

*(vacío — backlog abajo)*

---

## ⚪ Backlog

- [API-012] Docker out-of-the-box — `docker/entrypoint.sh` idempotente ✅ (2026-05-15). Pendiente: orquestación cross-repo en `ci4-kickstart` (coordinada con kickstart v1.1.0+).

---

## ⚠️ Fuera de alcance / señales

- [API-014] Soporte multi-tenant nativo — decisión registrada en `docs/adr/ADR-011-multi-tenancy-out-of-scope.md`. Reactivar solo si aparece una señal real (tenant con SLA propio, aislamiento físico requerido, etc.).
- [SEÑAL-API-001] `InvalidChars` global filter rompe con ints en JSON body. CI4 4.7's `InvalidChars::checkEncoding` llama `mb_check_encoding($value, 'UTF-8')` sobre cada hoja recursivamente; cuando el body lleva enteros (p.ej. `{"ids":[1,2]}`) lanza `TypeError`. Workaround actual: cliente debe stringificar (el admin's `FileApiService::bulk*` ya lo hace; documentado en OpenAPI). **Señal de activación:** cuando aparezca un segundo endpoint que reciba arrays de ints o cuando upstream-CI4 publique fix. **Acción:** o (a) PR upstream a CI4 para que `checkEncoding` haga `is_string($value) ? mb_check_encoding(...) : true`, o (b) wrapper local en `Config\Filters` que envuelva el filter.
- [BACKLOG] Files — endpoints sueltos que el admin llama pero el API aún no expone (post-API-015): `PATCH /files/{id}` (alt_text/caption/credit), `POST /files/{id}/replace`, `POST /files/{id}/regenerate-variants`, `GET /files/{id}/usages`. Crear tareas individuales cuando los necesites.

---

## 🏗️ Contratos de arquitectura

- **DTO-First:** toda entrada y salida de Controllers usa DTOs. Nunca arrays raw.
- **Services puros:** no conocen HTTP ni `$request`. Reciben DTOs, devuelven DTOs o lanzan excepciones de dominio.
- **Controllers delgados:** usar `handleRequest()` de `ApiController`. Sin lógica de negocio.
- **Separador de permisos:** punto `.` (NO `:`). Razón: `Filters::getCleanName()` hace `explode(':')` y trunca silenciosamente.
- **Rutas por dominio:** `app/Config/Routes/v1/<dominio>.php`.
- **Tests:** todo endpoint nuevo necesita al menos un test Feature.
- **CRUD nuevo:** usar `bash vendor/bin/make-crud.sh` siempre. Nunca crear DTOs manualmente.
- **OpenAPI:** correr `php spark swagger:generate` al terminar cualquier endpoint nuevo.
- **Migraciones:** nunca modificar migraciones existentes. Nueva migración para cualquier cambio de schema.

### 🚧 Technical Debt (IAM & Models)
- [x] **Automatic App Inference**: Modify PermissionService::beforeStore to automatically fill application_id using the request's X-App-Key if not provided. ✅ 2026-05-25
- [x] **Audit Compliance**: Remediate `AuditLogModel` to inherit from `BaseAuditableModel` to ensure automated audit trail consistency. ✅ 2026-05-26
- [x] **Model Audit Audit**: Perform a full audit of all models in `app/Models` to ensure they either extend `BaseAuditableModel` or are explicitly excluded from auditing. ✅ 2026-05-26

### 🏗️ Technical Debt (Architecture & DTO-First)
- [x] **Service Layer DTO Audit**: Investigate and refactor Service layer methods currently using raw arrays (`array`) for parameters and return types (e.g., `GalleryService`, `Iam/*Service`). Goal: Replace with typed `readonly` DTOs. ✅ 2026-05-26
- [x] **DTO-First Enforcement**: Add a static analysis rule (e.g., PHPStan custom rule) to flag usage of `array` as type-hint in `app/Services` to prevent future regressions. ✅ 2026-05-28

### 🛠️ Refactorización (PHPStan)
- [x] **Fase 1: Core hardening** — Tipar `RepositoryInterface` y `AuditServiceInterface` en `ci4-api-core`.
- [x] **Fase 2: ApiController Boundary** — Tipar `ApiController` en `ci4-api-core` para eliminar `missingType.iterableValue` del baseline.
- [x] **Fase 3: Implementación Estricta** — Corregir controladores y servicios en `ci4-api-starter` tras el tipado del core. ✅ 2026-05-26
- [x] **Fase 4: Scaffolding Generator** — Actualizar plantillas de `ci4-api-scaffolding` para generar código con tipos explícitos. ✅ 2026-05-26
