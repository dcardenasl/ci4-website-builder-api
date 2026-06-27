# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **`EffectivePermissionsResolver::resolveAll()`** — new method to resolve all permissions for a user across all applications (cross-app permission resolution with caching)
- **`RemoveMirroredPermissions` command** — administrative command to clean up mirrored permission assignments
- **`FileService`** — automatic file categorization by MIME type (image, video, audio, document)
- **`ImageVariantProcessor`** — new `lg` (1200px) image variant and improved aspect-ratio-preserving resize logic
- **`InternalEmailController`** — new internal API endpoint for async email queue delivery (`POST /api/v1/internal/email/queue`)

### Fixed

- **`JwtService`** — enforce required `app.baseURL` configuration via validation and improved error messages with i18n support
