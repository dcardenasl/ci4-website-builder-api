# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Locale-aware Google pending-approval email** — the Google login endpoint accepts an optional locale and queues the subject/template in the caller's supported language.
- **Public-site cache permissions** — seeded `system.public-cache.read` and
  `system.public-cache.invalidate` for the admin cache status and invalidation consumers.
- **Cached file picker manifest** — lightweight metadata/preview endpoint with mutation-aware version invalidation.
- **`files:audit`** — read-only CLI audit that compares local upload files with original and
  variant paths persisted in the `files` table, reporting disk/database discrepancies without
  modifying either side.
- **`RequestLogModel::getDashboardStats()`** — lightweight availability summary (total/successful/failed requests, error rate, availability) for the admin dashboard widget, without paying the cost of percentile/slow-request analysis it doesn't display.
- **Centralized file authorization policy** — file actions now use a closed action enum, require `files.read`/`files.write` or `files.admin` as appropriate, and no longer accept caller-controlled ownership bypass flags.
- **`NullVirusScannerService`** — explicit placeholder scanner that fails closed when virus scanning is enabled without a real integration.
- **Refresh-token family lifecycle** — family/parent lineage, reuse detection, per-user access-token versions, and immediate invalidation after account-wide revocation.
- **User role composition** — custom profiles now retain the starter's baseline `user` role and its generic self/file permissions.
- **Request DTO nullable-field preservation** — request payloads now distinguish an omitted
  nullable field from an explicit `null`, so callers can intentionally clear optional API key,
  profile, file metadata, gallery, IAM, and user fields.

### Changed
- **File policy configuration** — removed the obsolete `FILES_USER_SCOPED` example; `FILE_USER_SCOPED_FILES` is the single supported setting.
- **Runtime logging configuration** — map `LOG_LEVEL` to CodeIgniter thresholds and skip request-logging filter dispatch when request logging is explicitly disabled.
- **Runtime performance configuration** — added opt-in APCu caching with a file fallback and enabled persistent database connections only in production.
- **Public static assets** — serve uploaded image/font variants with one-year immutable cache headers at the Apache and `.htaccess` layers.
- **IAM persistence access** — migrated the nine IAM services from raw query-builder access to typed model finder/mutator methods and added a zero-tolerance architecture guard.
- **Starter runtime defaults** — documented the generic Hub security contracts and aligned local
  `.env`/init/OpenAPI/CORS defaults with the `8180` kit port series.
- **`RequestLogModel::getStats()`** — consolidated from ~8 separate round-trips into one aggregation query plus one percentile query using window functions.

### Fixed
- **Cross-domain file usage guard** — file deletion, purge, usage reads, and replacement now
  include references reported by the configured CMS domain; successful mutations notify that
  domain to invalidate stale file metadata.
- **File public URLs** — resolve response and internal metadata URLs from the current storage
  path instead of persisting deployment hosts; existing file rows are normalized by migration
  and download responses also use the active storage driver.
- **`LocalDriver`** — use an explicit `PortableVisibilityConverter` instead of relying on Flysystem's implicit default, so stored file permissions are predictable.
- **`UpdateFileMetadataRequestDTO`** — accept an explicit `null` metadata field as a valid clear
  operation instead of treating it as an empty update.
- **File upload policy** — simulated ClamAV scans can no longer report unscanned files as safe.
- **Refresh-token revocation cache** — negative cache entries are no longer stored, so a newly revoked access token cannot be hidden by a stale negative result.

### Changed
- **`dcardenasl/ci4-api-core`** — bumped constraint from `^1.0` to `^1.5`; adjusted `ApiKeyRepositoryInterface::findAll()`'s test double for the package's updated signature (`?int $limit = null`).

### Security
- **`codeigniter4/framework`** — bumped to v4.7.4, closing CVE-2026-63221 (critical, SQLi in `deleteBatch()`), CVE-2026-63222 (high, path traversal in `UploadedFile::move()`), and CVE-2026-63220 (medium, header spoofing in `isSecure()`).
- **`guzzlehttp/guzzle`** — bumped to 7.15.5 (transitive via `aws/aws-sdk-php`, `google/apiclient`, `google/auth`), closing CVE-2026-69246/69245. None of the affected code paths are exercised by this app; verified via `composer audit`.

## [1.0.0] — 2026-07-23

### Added

- **`MetricsController::timeseries()` endpoint** — new `GET /api/v1/admin/metrics/timeseries` endpoint for time-bucketed request/error/latency series suitable for trend charts in analytics dashboards; parameters include `interval`, `limit`, and optional `start_date`/`end_date`
- **`FilePolicyService`** — centralized service for file visibility and access control policies; supports configurable default visibility, public/private restrictions, owner-scoped listings, and privileged read bypass
- **`EffectivePermissionsResolver::resolveAll()`** — new method to resolve all permissions for a user across all applications (cross-app permission resolution with caching)
- **`RemoveMirroredPermissions` command** — administrative command to clean up mirrored permission assignments
- **`FileService`** — automatic file categorization by MIME type (image, video, audio, document)
- **`ImageVariantProcessor`** — WebP conversion with format-specific quality optimization; enhanced variant metadata (size bytes, MIME type); improved aspect-ratio-preserving resize with edge-case handling
- **`InternalEmailController`** — new internal API endpoint for async email queue delivery (`POST /api/v1/internal/email/queue`)
- **`InternalFileMetaController`** — new internal M2M endpoint for batch-resolving file metadata by ID (`GET /api/v1/internal/files/batch-meta`)

### Changed

- **`FileService`** — replaced `FilenameGenerator` with `StorageKeyGenerator` for opaque, collision-resistant storage keys; now includes content-hash-based duplicate detection

### Fixed

- **Production Docker image shipped 2 kernel-header HIGH CVEs (CVE-2026-53399, CVE-2026-64600)** — the `php:8.2-apache` base image's `linux-libc-dev` package was behind the Debian patch level; `apt-get upgrade` now runs before `apt-get install` in the image's system-dependency layer so patched OS packages are picked up even when the base image tag itself hasn't been rebuilt.
- **`MultipartProcessor`** — validate real MIME type (via `fileinfo`) against declared extension to detect spoofing attacks (e.g., `.jpg` with `application/zip` content); logs warning and rejects with `file_mime_mismatch` error
- **`FileService::destroy()`** — prevent deletion of files with active references (e.g. used by pages, blocks); throws `ConflictException` with resource count
- **`JwtService`** — enforce required `app.baseURL` configuration via validation and improved error messages with i18n support
- **`AuthThrottleFilter`** — apply a stricter per-route rate limit override to `auth/login` and key the throttle cache by IP + path instead of IP alone, preventing login brute-force attempts from sharing headroom with other auth routes
- **`AuthThrottleFilter`** — raise the per-route override for `auth/refresh` to 30 requests/hour instead of sharing the strict 3/hour login-brute-force limit, since token refresh requires an already-valid refresh token and isn't a guessable-credential vector
- **`Api` config** — relax the auth rate limit to 20 requests/5min under `ENVIRONMENT === 'development'`, keeping the strict 3/hour limit in production; the tighter limit was blocking local dev/test workflows
- **`AuthThrottleFilter`** — remove the `auth/login` route-specific override (5 requests/hour); the general `authRateLimitRequests` config is already the stricter value, so the override could only ever loosen the effective limit
