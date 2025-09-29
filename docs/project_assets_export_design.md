# Project Assets & Export Package Design

This document describes how to implement a **Project Assets Folder** and **One-Click Export Package** feature for the dual-surface architecture.

---

## 1. Storage Layout (Laravel Filesystem)

```
storage/app/workspaces/{workspace_id}/projects/{project_id}/
  assets/         # raw uploads (docs, images, CAD, etc.)
  exports/        # generated .zip bundles and manifests
  derived/        # OCR, text extracts, thumbnails, embeddings
```

- Files remain scoped by `workspace_id` and `project_id`.
- Assets are always tied to their project folder.
- Derived artifacts (OCR text, thumbnails, embeddings) stay colocated.

---

## 2. Schema Additions

### Files Table (existing)
- `workspace_id`
- `project_id`
- `path`
- `mime_type`
- `size`
- `user_id` (uploader)

### New: `collection` column
- Lightweight grouping/tag, e.g. `"contracts"`, `"photos"`, `"plans"`.

### New: `project_exports` table

| Column       | Type        | Notes |
|--------------|------------|-------|
| id           | bigint PK  |       |
| workspace_id | FK         | Cascade delete |
| project_id   | FK         | Cascade delete |
| status       | string     | `pending` \| `building` \| `ready` \| `failed` |
| zip_path     | string     | Path to generated zip |
| manifest     | json       | Metadata snapshot |
| created_by   | FK (users) | Who triggered the export |
| timestamps   |            | |

---

## 3. Permissions

- **Admins**: Can export any project.
- **Project Members**: Can only export projects they are assigned to.
- **Clients**: Optional read-only export access.

Permissions reuse existing workspace/project scoping.

---

## 4. Export Process

1. **User triggers export** (from Filament Admin or SPA).
2. **Job queued**:
   - Collect all project files + metadata.
   - Generate manifest JSON (project, client, tasks, files list).
   - Bundle into `.zip` and store in `/exports`.
3. **Database record updated** (`project_exports` row set to `ready`).
4. **Notification sent** to user with download link.

---

## 5. AI Integration

- **Context Pond**: AI agents query the `derived/` folder for OCR text, embeddings, and summaries.
- **Client Queries**: AI can pull answers from the manifest + project assets structure.

---

## 6. Benefits

- **One-click package** for clients or compliance.
- **Consistent structure** across all projects.
- **Foundation for AI context building**.
- **Extensible**: add new asset types or derived data without breaking layout.
