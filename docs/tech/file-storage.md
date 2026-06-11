# File Storage

File management follows a decomposed architecture to handle multiple input types and storage drivers seamlessly.

Key Components:
- **`app/Services/Files/FileService.php`**: Orchestrates storage and database persistence. Owns the soft-delete / restore / force-delete lifecycle (see below).
- **`app/Interfaces/Files/FileRepositoryInterface.php`**: Standardizes metadata retrieval and persistence; adds `findIncludingTrashed()` and `purge()` for trash-aware reads/writes.
- **`app/Libraries/Files/MultipartProcessor.php`**: Handles standard HTTP file uploads.
- **`app/Libraries/Files/Base64Processor.php`**: Decodes and validates Data URIs and raw Base64.
- **`app/Libraries/Files/FilenameGenerator.php`**: Sanitizes names and prevents storage collisions.
- **`app/Support/Files/ProcessedFile.php`**: Standardized value object for stream-based transfers.

Storage Drivers (`app/Libraries/Storage/`):
- **LocalDriver**: Stores files in `writable/uploads/`.
- **S3Driver**: Integrates with AWS S3 using flysystem.

Environment Variables:
- `FILE_STORAGE_DRIVER`: `local` or `s3`.
- `FILE_MAX_SIZE`: Limit in bytes.
- `FILE_ALLOWED_TYPES`: Comma-separated extensions (e.g., `jpg,png,pdf`).

Validation:
All file operations use DTO-based validation. The processors ensure that files are structurally sound and safe before the `FileService` attempts persistence.

---

## Soft Delete & Trash

Files use CI4's native soft-delete mechanism. The `files` table carries two
trash-related columns (migration `2026-05-17-045115_AddSoftDeleteToFilesTable`):

- `deleted_at` — nullable `DATETIME`. `null` = live file; non-null = in trash.
- `deleted_by_user_id` — nullable `INT UNSIGNED`. Records who trashed it; cleared on
  restore. No FK because SQLite (used by the test harness) does not support adding
  FKs to existing tables — integrity is enforced at the service layer.

`FileModel::$useSoftDeletes = true` so `find()`, listings, and `paginateCriteria()`
exclude trashed rows by default.

### Lifecycle

| Action | Endpoint | DB effect | Storage effect |
|---|---|---|---|
| Trash | `DELETE /api/v1/files/{id}` | sets `deleted_at`, `deleted_by_user_id` | **preserved** — bytes still on disk |
| Restore | `POST /api/v1/files/{id}/restore` | clears `deleted_at`, `deleted_by_user_id` | n/a |
| Force delete | `DELETE /api/v1/files/{id}/force` | row purged | bytes removed from storage |

Calling `DELETE /files/{id}` on an already-trashed file returns **404** — the file is
already invisible to default queries (intentional REST semantics).
`POST /restore` and `DELETE /force` on a non-trashed file return **400**.

### Listing the trash

The `GET /api/v1/files` endpoint accepts a `trashed` query parameter:

- `trashed=without` (default) — only live files.
- `trashed=only` — only trashed files (trash bin view).
- `trashed=with` — both, useful for admin tools that show everything.

Anything else falls back to `without`.

### Bulk endpoints

For trash UI multi-select:

- `POST /api/v1/files/bulk-delete` `{ "ids": ["1", "2", "3"] }` — bulk trash.
- `POST /api/v1/files/bulk-restore` — bulk restore.
- `POST /api/v1/files/bulk-force-delete` — bulk permanent delete.

Each returns a per-item outcome so partial successes are reportable to the UI:

```json
{
  "status": "success",
  "data": [
    { "id": 1, "ok": true },
    { "id": 2, "ok": true },
    { "id": 999, "ok": false, "error": "File not found" }
  ]
}
```

**Note on `ids` typing.** Send ids as **strings**, not integers, in the JSON body.
CI4's global `InvalidChars` filter recurses into the request body and calls
`mb_check_encoding()` on each leaf; raw integers trigger a `TypeError`. The DTO
casts strings back to `int` internally. The admin's `FileApiService::bulk*` already
stringifies. Tracked as `SEÑAL-API-001` in `TASKS.md`.

### Authorization

`FileService::destroy()`, `restore()`, and `forceDestroy()` all run through
`findFileAndAuthorize()` (or its trashed-aware sibling `findTrashedFileAndAuthorize()`),
which enforces ownership unless the caller carries the `files.read` permission
(treated as the "files admin" bypass). Denied attempts are written to the audit log
with action codes `unauthorized_file_{delete,restore,force_delete}`.
