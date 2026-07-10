# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

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

- **`MultipartProcessor`** — validate real MIME type (via `fileinfo`) against declared extension to detect spoofing attacks (e.g., `.jpg` with `application/zip` content); logs warning and rejects with `file_mime_mismatch` error
- **`FileService::destroy()`** — prevent deletion of files with active references (e.g. used by pages, blocks); throws `ConflictException` with resource count
- **`JwtService`** — enforce required `app.baseURL` configuration via validation and improved error messages with i18n support
- **`AuthThrottleFilter`** — apply a stricter per-route rate limit override to `auth/login` and key the throttle cache by IP + path instead of IP alone, preventing login brute-force attempts from sharing headroom with other auth routes
- **`AuthThrottleFilter`** — raise the per-route override for `auth/refresh` to 30 requests/hour instead of sharing the strict 3/hour login-brute-force limit, since token refresh requires an already-valid refresh token and isn't a guessable-credential vector
