# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **`EffectivePermissionsResolver::resolveAll()`** — new method to resolve all permissions for a user across all applications (cross-app permission resolution with caching)
- **`RemoveMirroredPermissions` command** — administrative command to clean up mirrored permission assignments
- **`FileService`** — automatic file categorization by MIME type (image, video, audio, document)
- **`ImageVariantProcessor`** — WebP conversion with format-specific quality optimization; enhanced variant metadata (size bytes, MIME type); improved aspect-ratio-preserving resize with edge-case handling
- **`InternalEmailController`** — new internal API endpoint for async email queue delivery (`POST /api/v1/internal/email/queue`)
- **`InternalFileMetaController`** — new internal M2M endpoint for batch-resolving file metadata by ID (`GET /api/v1/internal/files/batch-meta`)

### Changed

- **`FileService`** — replaced `FilenameGenerator` with `StorageKeyGenerator` for opaque, collision-resistant storage keys; now includes content-hash-based duplicate detection

### Fixed

- **`JwtService`** — enforce required `app.baseURL` configuration via validation and improved error messages with i18n support
