# Project Assets & Export Package Architecture

**Version:** 1.0
**Last Updated:** September 29, 2025
**Status:** Implementation Ready

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Core Concepts](#core-concepts)
3. [Architecture Overview](#architecture-overview)
4. [Project Assets Structure](#project-assets-structure)
5. [Export System](#export-system)
6. [AI Integration](#ai-integration)
7. [Implementation Guide](#implementation-guide)
8. [API Endpoints](#api-endpoints)
9. [Security & Permissions](#security--permissions)
10. [Use Cases](#use-cases)

---

## Executive Summary

This architecture extends the dual-surface platform (System Admin + App) with a **Project Assets system** that:

- **Centralizes all project data** in a structured folder hierarchy
- **Enables one-click exports** for client delivery, handoffs, and compliance
- **Powers AI context** by maintaining a single source of truth per project
- **Automates derived data** (OCR, thumbnails, embeddings) for enhanced search and AI queries

### Key Benefits

✅ **Single Source of Truth** — Everything lives in the project assets folder
✅ **Easy Handoffs** — Download complete project packages with one click
✅ **AI-Ready** — Structured data feeds AI agents with full context
✅ **Workspace Scoped** — Inherits all security from the workspace → project model
✅ **Client-Friendly** — Branded export packages for professional delivery

---

## Core Concepts

### Hierarchy

```
Workspace (Admin creates)
  └── Project (Admin assigns, team collaborates)
      └── Project Assets Folder (Auto-created)
          ├── Assets/ (Uploaded files)
          ├── Derived/ (OCR, thumbnails, embeddings)
          ├── Exports/ (Zipped packages)
          └── Metadata/ (Project info, tasks, client data)
```

### Three Data Layers

1. **Assets** — Raw uploaded files (PDFs, images, documents)
2. **Derived** — Processed data (OCR text, thumbnails, vector embeddings)
3. **Metadata** — Structured JSON (project info, tasks, client details, activity log)

### Export Philosophy

> "Every project should be deliverable as a complete, self-contained package at any moment."

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    User Interface Layer                      │
├──────────────────────────┬──────────────────────────────────┤
│   System Admin (/admin)  │        App (/app)                │
│   - Create workspaces    │   - Manage projects              │
│   - Assign admins        │   - Upload files                 │
│   - View all projects    │   - Assign tasks                 │
│                          │   - Export packages              │
│                          │   - Query AI agents              │
└──────────────────────────┴──────────────────────────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      API Layer (Laravel)                     │
│   - File upload & storage                                   │
│   - Export generation (ZIP)                                 │
│   - Metadata compilation                                    │
│   - OCR & thumbnail processing                              │
└─────────────────────────────────────────────────────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                  Project Assets Storage                      │
│                                                              │
│  storage/projects/{workspace_id}/{project_id}/              │
│  ├── assets/                                                │
│  │   ├── documents/                                         │
│  │   ├── images/                                            │
│  │   └── contracts/                                         │
│  ├── derived/                                               │
│  │   ├── ocr/         (extracted text)                      │
│  │   ├── thumbnails/  (image previews)                      │
│  │   └── embeddings/  (vector representations)             │
│  ├── exports/                                               │
│  │   └── project_export_2025_09_29.zip                     │
│  └── metadata/                                              │
│      ├── project.json                                       │
│      ├── tasks.json                                         │
│      ├── client.json                                        │
│      └── activity.json                                      │
└─────────────────────────────────────────────────────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    AI Context Layer                          │
│   - Reads from derived/ for OCR text                        │
│   - Uses embeddings/ for semantic search                    │
│   - Pulls metadata/ for structured queries                  │
│   - Generates summaries and answers                         │
└─────────────────────────────────────────────────────────────┘
```

---

## Project Assets Structure

### File Organization

```
storage/projects/{workspace_id}/{project_id}/
│
├── assets/                          # Raw uploaded files
│   ├── documents/
│   │   ├── contract_signed.pdf
│   │   └── scope_of_work.docx
│   ├── images/
│   │   ├── site_photo_001.jpg
│   │   └── floor_plan.png
│   ├── contracts/
│   │   └── client_agreement.pdf
│   └── misc/
│       └── budget_spreadsheet.xlsx
│
├── derived/                         # Processed data
│   ├── ocr/
│   │   ├── contract_signed.txt
│   │   └── scope_of_work.txt
│   ├── thumbnails/
│   │   ├── site_photo_001_thumb.jpg
│   │   └── floor_plan_thumb.png
│   └── embeddings/
│       ├── contract_signed.json     # Vector embeddings
│       └── scope_of_work.json
│
├── exports/                         # Generated export packages
│   ├── project_export_2025_09_29.zip
│   └── project_export_2025_09_15.zip
│
└── metadata/                        # Structured data snapshots
    ├── project.json                 # Name, description, status, dates
    ├── tasks.json                   # All tasks with assignments
    ├── client.json                  # Client contact info & notes
    ├── activity.json                # Activity log
    └── manifest.json                # File index with checksums
```

### Metadata Format Examples

#### project.json
```json
{
  "id": 123,
  "workspace_id": 5,
  "name": "Downtown Office Renovation",
  "description": "Complete renovation of 5000 sq ft office space",
  "status": "in_progress",
  "client": {
    "name": "Acme Corporation",
    "contact": "John Doe",
    "email": "john@acme.com",
    "phone": "+1-555-0100"
  },
  "dates": {
    "created_at": "2025-01-15T10:00:00Z",
    "start_date": "2025-02-01",
    "target_completion": "2025-06-30"
  },
  "budget": {
    "total": 150000,
    "currency": "USD"
  },
  "team": [
    { "user_id": 10, "name": "Jane Smith", "role": "project_manager" },
    { "user_id": 15, "name": "Bob Builder", "role": "contractor" }
  ]
}
```

#### tasks.json
```json
{
  "tasks": [
    {
      "id": 45,
      "title": "Obtain building permits",
      "status": "completed",
      "priority": "high",
      "assigned_to": { "id": 10, "name": "Jane Smith" },
      "due_date": "2025-01-30",
      "completed_at": "2025-01-28T14:30:00Z"
    },
    {
      "id": 46,
      "title": "Demolition phase",
      "status": "in_progress",
      "priority": "high",
      "assigned_to": { "id": 15, "name": "Bob Builder" },
      "due_date": "2025-02-15",
      "progress": 60
    }
  ]
}
```

#### manifest.json
```json
{
  "generated_at": "2025-09-29T12:00:00Z",
  "project_id": 123,
  "files": [
    {
      "path": "assets/documents/contract_signed.pdf",
      "size": 2456789,
      "mime_type": "application/pdf",
      "checksum": "sha256:abc123...",
      "uploaded_at": "2025-01-15T10:30:00Z",
      "uploaded_by": { "id": 10, "name": "Jane Smith" },
      "has_ocr": true,
      "has_thumbnail": false
    },
    {
      "path": "assets/images/site_photo_001.jpg",
      "size": 1234567,
      "mime_type": "image/jpeg",
      "checksum": "sha256:def456...",
      "uploaded_at": "2025-02-05T09:15:00Z",
      "uploaded_by": { "id": 15, "name": "Bob Builder" },
      "has_ocr": false,
      "has_thumbnail": true
    }
  ]
}
```

---

## Export System

### One-Click Export Flow

```
User clicks "Export Project"
         ↓
Generate fresh metadata snapshots
         ↓
Create export manifest
         ↓
Bundle into ZIP archive
         ↓
Store in exports/ folder
         ↓
Return download link
```

### Export Package Contents

```
project_export_2025_09_29.zip
├── README.txt                        # Human-readable summary
├── metadata/
│   ├── project.json
│   ├── tasks.json
│   ├── client.json
│   ├── activity.json
│   └── manifest.json
├── assets/
│   ├── documents/
│   ├── images/
│   └── contracts/
└── derived/
    ├── ocr/
    └── thumbnails/
```

### README.txt Template

```
PROJECT EXPORT: Downtown Office Renovation
Generated: September 29, 2025 at 12:00 PM
Workspace: Acme Projects
Project ID: 123

========================================
PROJECT SUMMARY
========================================
Client: Acme Corporation
Contact: John Doe (john@acme.com)
Status: In Progress
Start Date: February 1, 2025
Target Completion: June 30, 2025
Budget: $150,000 USD

========================================
TEAM
========================================
- Jane Smith (Project Manager)
- Bob Builder (Contractor)

========================================
CONTENTS
========================================
This package contains:
- All project files (documents, images, contracts)
- Task list with assignments and status
- Client contact information
- Activity log
- OCR-extracted text from documents
- Image thumbnails

For full details, see metadata/project.json

========================================
GENERATED BY
========================================
YourApp Platform
https://yourdomain.com
```

### Export API Endpoint

```php
// POST /api/projects/:id/export
{
  "include": {
    "assets": true,
    "derived": true,
    "metadata": true,
    "activity_log": true
  },
  "format": "zip",
  "password_protect": false
}

// Response
{
  "export_id": "abc123",
  "download_url": "/api/exports/abc123/download",
  "size": 45678901,
  "expires_at": "2025-10-06T12:00:00Z"
}
```

---

## AI Integration

### How AI Uses Project Assets

```
User Query: "What's the status of the building permits?"
              ↓
AI Agent reads metadata/tasks.json
              ↓
Finds task: "Obtain building permits"
              ↓
Checks derived/ocr/permit_application.txt
              ↓
Response: "The building permits were completed on Jan 28.
The permit documents are available in the project assets."
```

### AI Context Sources

1. **Metadata JSON** — Structured queries (task status, team members, dates)
2. **OCR Text** — Full-text search in documents
3. **Embeddings** — Semantic similarity search
4. **Activity Log** — Timeline of changes

### Example AI Queries

| Query | Data Source | Response |
|-------|-------------|----------|
| "Who's assigned to demolition?" | `tasks.json` | "Bob Builder is assigned to the demolition phase task." |
| "What does the contract say about payment terms?" | `derived/ocr/contract_signed.txt` | "The contract specifies net-30 payment terms..." |
| "Show me similar projects" | `derived/embeddings/` | Lists projects with semantic similarity |
| "What happened last week?" | `metadata/activity.json` | "Last week: 3 tasks completed, 2 files uploaded..." |

### AI Agent Implementation

```php
// app/Services/AI/ProjectContextService.php
class ProjectContextService
{
    public function buildContext(Project $project): array
    {
        return [
            'project' => $this->loadProjectMetadata($project),
            'tasks' => $this->loadTasksMetadata($project),
            'documents' => $this->loadDocumentText($project),
            'recent_activity' => $this->loadActivityLog($project, days: 7),
        ];
    }

    public function queryProject(Project $project, string $query): string
    {
        $context = $this->buildContext($project);

        return $this->aiService->query([
            'system' => "You are a project assistant. Answer questions using only the provided project context.",
            'context' => json_encode($context),
            'query' => $query,
        ]);
    }
}
```

---

## Implementation Guide

### Phase 1: File Storage Foundation

#### 1.1 Create Storage Structure

```php
// app/Services/ProjectAssets/StorageService.php
class StorageService
{
    public function createProjectFolder(Project $project): void
    {
        $basePath = $this->getProjectPath($project);

        Storage::makeDirectory("{$basePath}/assets/documents");
        Storage::makeDirectory("{$basePath}/assets/images");
        Storage::makeDirectory("{$basePath}/assets/contracts");
        Storage::makeDirectory("{$basePath}/derived/ocr");
        Storage::makeDirectory("{$basePath}/derived/thumbnails");
        Storage::makeDirectory("{$basePath}/derived/embeddings");
        Storage::makeDirectory("{$basePath}/exports");
        Storage::makeDirectory("{$basePath}/metadata");
    }

    public function getProjectPath(Project $project): string
    {
        return "projects/{$project->workspace_id}/{$project->id}";
    }
}
```

#### 1.2 Update File Upload

```php
// app/Services/ProjectAssets/FileUploadService.php
class FileUploadService
{
    public function upload(Project $project, UploadedFile $file, string $type): File
    {
        $basePath = $this->storage->getProjectPath($project);
        $category = $this->categorizeFile($file); // documents, images, contracts

        $path = $file->store("{$basePath}/assets/{$category}");

        $fileRecord = File::create([
            'project_id' => $project->id,
            'workspace_id' => $project->workspace_id,
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'category' => $category,
            'uploaded_by' => auth()->id(),
        ]);

        // Queue processing jobs
        ProcessOCR::dispatch($fileRecord);
        GenerateThumbnail::dispatch($fileRecord);

        return $fileRecord;
    }
}
```

---

### Phase 2: Derived Data Processing

#### 2.1 OCR Job

```php
// app/Jobs/ProcessOCR.php
class ProcessOCR implements ShouldQueue
{
    public function handle(File $file, OCRService $ocr)
    {
        if (!$this->shouldProcessOCR($file)) {
            return;
        }

        $text = $ocr->extract(Storage::path($file->path));

        $ocrPath = str_replace('/assets/', '/derived/ocr/', $file->path);
        $ocrPath = str_replace(['.pdf', '.docx'], '.txt', $ocrPath);

        Storage::put($ocrPath, $text);

        $file->update(['has_ocr' => true, 'ocr_path' => $ocrPath]);
    }

    private function shouldProcessOCR(File $file): bool
    {
        return in_array($file->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}
```

#### 2.2 Thumbnail Job

```php
// app/Jobs/GenerateThumbnail.php
class GenerateThumbnail implements ShouldQueue
{
    public function handle(File $file, ImageService $images)
    {
        if (!$this->shouldGenerateThumbnail($file)) {
            return;
        }

        $thumbnail = $images->createThumbnail(
            Storage::path($file->path),
            width: 300,
            height: 300
        );

        $thumbPath = str_replace('/assets/', '/derived/thumbnails/', $file->path);
        $thumbPath = preg_replace('/\.\w+$/', '_thumb.jpg', $thumbPath);

        Storage::put($thumbPath, $thumbnail);

        $file->update(['has_thumbnail' => true, 'thumbnail_path' => $thumbPath]);
    }

    private function shouldGenerateThumbnail(File $file): bool
    {
        return str_starts_with($file->mime_type, 'image/');
    }
}
```

#### 2.3 Embeddings Job

```php
// app/Jobs/GenerateEmbeddings.php
class GenerateEmbeddings implements ShouldQueue
{
    public function handle(File $file, EmbeddingService $embeddings)
    {
        if (!$file->has_ocr) {
            return; // Need OCR text first
        }

        $text = Storage::get($file->ocr_path);
        $vector = $embeddings->generate($text);

        $embeddingPath = str_replace('/derived/ocr/', '/derived/embeddings/', $file->ocr_path);
        $embeddingPath = str_replace('.txt', '.json', $embeddingPath);

        Storage::put($embeddingPath, json_encode([
            'file_id' => $file->id,
            'vector' => $vector,
            'dimensions' => count($vector),
            'generated_at' => now()->toIso8601String(),
        ]));

        $file->update(['has_embedding' => true, 'embedding_path' => $embeddingPath]);
    }
}
```

---

### Phase 3: Metadata Generation

#### 3.1 Metadata Service

```php
// app/Services/ProjectAssets/MetadataService.php
class MetadataService
{
    public function generateProjectMetadata(Project $project): array
    {
        return [
            'id' => $project->id,
            'workspace_id' => $project->workspace_id,
            'name' => $project->name,
            'description' => $project->description,
            'status' => $project->status,
            'client' => $this->getClientData($project),
            'dates' => $this->getProjectDates($project),
            'budget' => $project->budget,
            'team' => $this->getTeamMembers($project),
        ];
    }

    public function generateTasksMetadata(Project $project): array
    {
        return [
            'tasks' => $project->tasks()->with('assignee')->get()->map(fn($task) => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'assigned_to' => $task->assignee ? [
                    'id' => $task->assignee->id,
                    'name' => $task->assignee->name,
                ] : null,
                'due_date' => $task->due_date?->toDateString(),
                'completed_at' => $task->completed_at?->toIso8601String(),
            ]),
        ];
    }

    public function generateManifest(Project $project): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'project_id' => $project->id,
            'files' => $project->files->map(fn($file) => [
                'path' => str_replace("projects/{$project->workspace_id}/{$project->id}/", '', $file->path),
                'size' => $file->size,
                'mime_type' => $file->mime_type,
                'checksum' => hash_file('sha256', Storage::path($file->path)),
                'uploaded_at' => $file->created_at->toIso8601String(),
                'uploaded_by' => [
                    'id' => $file->uploader->id,
                    'name' => $file->uploader->name,
                ],
                'has_ocr' => $file->has_ocr,
                'has_thumbnail' => $file->has_thumbnail,
            ]),
        ];
    }

    public function saveAllMetadata(Project $project): void
    {
        $basePath = "projects/{$project->workspace_id}/{$project->id}/metadata";

        Storage::put("{$basePath}/project.json", json_encode(
            $this->generateProjectMetadata($project), JSON_PRETTY_PRINT
        ));

        Storage::put("{$basePath}/tasks.json", json_encode(
            $this->generateTasksMetadata($project), JSON_PRETTY_PRINT
        ));

        Storage::put("{$basePath}/manifest.json", json_encode(
            $this->generateManifest($project), JSON_PRETTY_PRINT
        ));
    }
}
```

---

### Phase 4: Export System

#### 4.1 Export Service

```php
// app/Services/ProjectAssets/ExportService.php
class ExportService
{
    public function exportProject(Project $project, array $options = []): Export
    {
        // Refresh metadata
        $this->metadata->saveAllMetadata($project);

        // Create export record
        $export = Export::create([
            'project_id' => $project->id,
            'workspace_id' => $project->workspace_id,
            'requested_by' => auth()->id(),
            'options' => $options,
            'status' => 'pending',
        ]);

        // Queue the export job
        GenerateExportPackage::dispatch($export);

        return $export;
    }
}
```

#### 4.2 Export Job

```php
// app/Jobs/GenerateExportPackage.php
class GenerateExportPackage implements ShouldQueue
{
    public function handle(Export $export, ZipService $zip)
    {
        $export->update(['status' => 'processing']);

        $project = $export->project;
        $basePath = "projects/{$project->workspace_id}/{$project->id}";
        $timestamp = now()->format('Y_m_d_His');
        $zipName = "project_export_{$timestamp}.zip";
        $zipPath = "{$basePath}/exports/{$zipName}";

        try {
            // Create ZIP
            $zip->create(Storage::path($zipPath));

            // Add README
            $zip->addFromString('README.txt', $this->generateReadme($project));

            // Add metadata
            $zip->addDirectory(Storage::path("{$basePath}/metadata"), 'metadata');

            // Add assets
            if ($export->options['include_assets'] ?? true) {
                $zip->addDirectory(Storage::path("{$basePath}/assets"), 'assets');
            }

            // Add derived data
            if ($export->options['include_derived'] ?? true) {
                $zip->addDirectory(Storage::path("{$basePath}/derived"), 'derived');
            }

            $zip->close();

            $export->update([
                'status' => 'completed',
                'file_path' => $zipPath,
                'file_size' => Storage::size($zipPath),
                'completed_at' => now(),
                'expires_at' => now()->addDays(7),
            ]);

        } catch (\Exception $e) {
            $export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    private function generateReadme(Project $project): string
    {
        return view('exports.readme', compact('project'))->render();
    }
}
```

---

## API Endpoints

### File Management

```
POST   /api/projects/:id/files               # Upload file
GET    /api/projects/:id/files               # List files
GET    /api/files/:id                        # File details
GET    /api/files/:id/download               # Download file
GET    /api/files/:id/thumbnail              # Get thumbnail
GET    /api/files/:id/ocr                    # Get OCR text
DELETE /api/files/:id                        # Delete file
```

### Export Management

```
POST   /api/projects/:id/export              # Request export
GET    /api/projects/:id/exports             # List exports
GET    /api/exports/:id                      # Export status
GET    /api/exports/:id/download             # Download export
DELETE /api/exports/:id                      # Delete export
```

### AI Queries

```
POST   /api/projects/:id/query               # Ask AI about project
GET    /api/projects/:id/context             # Get AI context data
POST   /api/projects/:id/summarize           # Generate project summary
```

---

## Security & Permissions

### File Upload Validation

```php
// app/Http/Requests/UploadFileRequest.php
class UploadFileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimes:pdf,doc,docx,jpg,jpeg,png,gif,xlsx,xls,txt',
            ],
            'category' => 'required|in:documents,images,contracts,misc',
        ];
    }
}
```

### Policy Example

```php
// app/Policies/FilePolicy.php
class FilePolicy
{
    public function download(User $user, File $file): bool
    {
        // User must be a member of the project's workspace
        return $user->workspace_id === $file->workspace_id;
    }

    public function delete(User $user, File $file): bool
    {
        // Only uploader or workspace admin can delete
        return $user->id === $file->uploaded_by
            || $user->hasWorkspacePermission('files.delete');
    }
}
```

### Export Access Control

```php
// Only project members can export
$this->authorize('view', $project);

// Exports expire after 7 days
if ($export->expires_at < now()) {
    abort(410, 'Export has expired');
}

// Track who downloads exports
ExportDownload::create([
    'export_id' => $export->id,
    'downloaded_by' => auth()->id(),
    'downloaded_at' => now(),
]);
```

---

## Use Cases

### Use Case 1: Client Delivery

**Scenario:** Project complete, need to deliver all files to client

```
1. Project Manager clicks "Export Project"
2. System generates fresh metadata snapshots
3. Creates branded ZIP package with all files
4. PM downloads and sends to client via email
5. Client receives professional package with README
```

### Use Case 2: Consultant Handoff

**Scenario:** Bringing new consultant onto existing project

```
1. Admin adds consultant to project team
2. Consultant clicks "Download Project Package"
3. Gets complete context: files, tasks, client info
4. Can start working immediately with full history
```

### Use Case 3: AI Project Assistant

**Scenario:** Team member asks AI about project status

```
User: "What's the status of the building permits?"

AI Process:
1. Loads metadata/tasks.json
2. Finds "Obtain building permits" task
3. Checks completion status and date
4. Reads derived/ocr/permit_application.txt for details

AI Response: "The building permits were completed on
January 28, 2025. The approved permit documents are
available in the project assets under contracts/."
```

### Use Case 4: Compliance Archive

**Scenario:** Need to archive project for legal compliance

```
1. Admin exports project with full derived data
2. ZIP package includes all original files + OCR text
3. Manifest provides checksums for file integrity
4. Store in secure archive with 7-year retention
```

---

## Next Steps

### Implementation Priority

1. **Phase 1:** File storage structure (Week 1)
   - Create folder hierarchy
   - Update file upload to use new structure
   - Test with real files

2. **Phase 2:** Derived data processing (Week 2)
   - Implement OCR job
   - Implement thumbnail generation
   - Test processing pipeline

3. **Phase 3:** Metadata generation (Week 3)
   - Build metadata service
   - Create JSON snapshots
   - Test data accuracy

4. **Phase 4:** Export system (Week 4)
   - Build export service
   - Create ZIP generation job
   - Add download UI

5. **Phase 5:** AI integration (Week 5)
   - Implement context builder
   - Connect to AI service
   - Test query responses

### Quick Start Checklist

- [ ] Update File model with new columns (category, ocr_path, thumbnail_path, has_ocr, has_thumbnail)
- [ ] Create StorageService for folder management
- [ ] Update FileUploadService to use new structure
- [ ] Create OCR and thumbnail processing jobs
- [ ] Build MetadataService for JSON generation
- [ ] Create Export model and migration
- [ ] Implement ExportService and job
- [ ] Add API endpoints for export management
- [ ] Build UI for export feature in React SPA
- [ ] Test end-to-end with real project data

---

## Conclusion

The Project Assets & Export Package system provides:

✅ **Organized Storage** — Every project has a structured folder
✅ **Easy Exports** — One-click package generation
✅ **AI Context** — Rich data for intelligent queries
✅ **Professional Delivery** — Branded packages for clients
✅ **Compliance Ready** — Complete audit trails

This architecture scales with your workspace → project hierarchy and integrates seamlessly with the dual-surface platform (System Admin + App).

---

**Document Status:** Implementation Ready
**Last Updated:** September 29, 2025
**Version:** 1.0
