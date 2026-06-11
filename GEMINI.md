# GEMINI.md - Project Context & Instructions

This file provides the foundational context and operational mandates for working within the **CodeIgniter 4 API Starter Kit**. Always adhere to these modern, high-stakes architecture standards.

## Project Overview

A production-ready REST API starter template with an advanced **Automated Scaffolding Engine**, strict typing, and comprehensive quality guardrails.

- **Primary Stack:** PHP 8.2+, MySQL 8.0+, CodeIgniter 4.
- **Architecture:** Layered REST API: **Controller → [RequestDTO] → Service → Repository → Model → Entity → [ResponseDTO]**.
- **Core Automation:** Modular CRUD Scaffolding, Automated Service Registration, Git Pre-commit Quality Enforcement.

## Essential Commands

### Development & Scaffolding
- `bash bin/make-crud.sh {Name} {Domain} '{fields}' yes [slug]`: Scaffold a 100% functional CRUD module (recommended — shell-safe).
- `php spark make:crud {Name} --domain {Domain}`: Interactive alternative for TTY sessions.
- `php spark module:check {Name} --domain {Domain}`: Validate generated wiring.
- `php spark swagger:generate`: Generate `public/swagger.json` from DTO schemas.
- `php spark migrate`: Apply database changes.

### Quality & Testing
- `composer quality`: Run full quality suite (CS-Check, PHPStan, i18n, Tests).
- `vendor/bin/phpunit`: Run all tests.

## Development Workflows

### 1. Automated Scaffolding (The Standard)
**All new resources must be generated via the scaffolding engine (`bin/make-crud.sh` / `make:crud`) to avoid human error.**
- **SSOT:** The generator synchronizes Migrations, DTOs, Entities, Models, and OpenAPI documentation from a single schema.
- **Smart Wiring:** The engine automatically registers the new domain and services in `Config/Services.php` and its traits.
- **Verification:** Every generation is backed by a Scaffolding Smoke Test to ensure syntactic correctness.

### 2. DTO-First Development (The "Shield" Pattern)
**All data transfer must use PHP 8.2 `readonly` classes.**
- **Immutability:** Use `readonly` for all DTOs and injected service properties.
- **Validation:** Constructor-based validation ensures that if a DTO exists, the data is guaranteed valid.

### 3. Pure Service Layer (The Engine)
- **Domain Logic Only:** Services must not touch global request state.
- **Generic Repository:** Use `GenericRepository` as default; escalate only for non-trivial domain queries.
- **Service Registration:** Automatically handled by `ConfigWireman` during scaffolding.

### 4. Living Documentation (OpenAPI)
- **Automatic Sync:** Endpoints and Schemas are automatically scaffolded into `app/Documentation/{Domain}/`.

## Quality Guardrails
- **Git Hooks:** Pre-commit hooks are automatically installed via `composer install`. Commits will fail if code style or static analysis (PHPStan) doesn't pass.
- **I18n Parity:** No hardcoded strings in DTOs or Services; use the scaffolded `Language/` keys.
- **Smoke Tests:** New modules must pass a smoke test before integration.

## Security Mandates
- **SQL Injection:** Always use CI4 Query Builder.
- **Audit Trail:** Automated via `Auditable` trait in models.
- **Secret Protection:** Never commit `.env` files.
