# Admin + Workspace Architecture Package

> This package defines how our two surfaces — **Admin (Filament)** and **Workspace (SPA)** — look, behave, and integrate. It includes plain-language explanations, ASCII wireframes, routing, auth, API contracts, realtime, deployment, and an MVP checklist.

---

## 1) Plain‑English Summary (How it feels to use)

- **/admin** = a classic, tidy **Admin Panel** (Filament). You pop in to manage users, check jobs/queues, view logs, tweak settings. It’s stable and minimal.
- **/workspace** = the real **app** (React SPA). It feels like ClickUp/Notion/Linear: fast, smooth navigation, modern UI, realtime updates. This is what your team and clients live in every day.
- Both share **one Laravel backend**, one login, one database. Two front doors, same house.

---

## 2) Wireframes (Text Mockups)

### 2.1 /admin — Filament (stock UI)

```
┌──────────────────────────────────────────────────────────────┐
│  Admin (Filament)                                            │
├───────────────┬───────────────────────────────────────────────┤
│ Navigation    │  Dashboard                                   │
│  • Dashboard  │  ┌─────────────────────────────────────────┐  │
│  • Users      │  │ Stats (Jobs, Failed Jobs, Queues, etc.)│  │
│  • Queues     │  └─────────────────────────────────────────┘  │
│  • Jobs       │                                               │
│  • Logs       │  Users (table):                               │
│  • Settings   │  [Name]  [Email]      [Role]     [Actions]    │
│               │  ------------------------------------------------
│               │  Jane    jane@…       admin      Edit | Delete │
│               │  John    john@…       team       Edit | Delete │
│               │                                               │
│               │  Horizon link → Queue/Worker dashboards        │
│               │  System logs → Viewer                          │
└───────────────┴───────────────────────────────────────────────┘
```

**Notes**

- Keep Filament **stock**: default tables, filters, actions.
- Use it for internal operations only.

---

### 2.2 /workspace — React SPA (Tailwind + shadcn)

#### A) Workspace Dashboard

```
┌──────────────────────────────────────────────────────────────┐
│  Topbar: [Workspace Switcher]  [Global Search]  [Profile]     │
├───────────────┬───────────────────────────────────────────────┤
│ Sidebar       │  Home                                         │
│  • Home       │  ┌───────Stats Cards────────────────────────┐ │
│  • Projects   │  │ Projects   Tasks Due  Members Online     │ │
│  • Tasks      │  └──────────────────────────────────────────┘ │
│  • Files      │                                               │
│  • Activity   │  Recent activity feed (real‑time)             │
│               │  - John created “Acme Site Revamp”            │
│               │  - Maria uploaded “wireframe.pdf”             │
└───────────────┴───────────────────────────────────────────────┘
```

#### B) Projects List

```
┌──────────────────────────────────────────────────────────────┐
│  Topbar …                                                     │
├───────────────┬───────────────────────────────────────────────┤
│ Sidebar …     │  Projects                                     │
│               │  [ + New Project ] [Filter] [Sort] [Search]    │
│               │  ┌──────────────────────────────────────────┐  │
│               │  │ • Acme Site Revamp  ▸ (Open)            │  │
│               │  │   tags: web, q4     last update: 2h ago │  │
│               │  │ • Internal Docs      ▸ (Open)           │  │
│               │  │   tags: ops          last update: 1d ago│  │
│               │  └──────────────────────────────────────────┘  │
└───────────────┴───────────────────────────────────────────────┘
```

#### C) Project Detail

```
┌──────────────────────────────────────────────────────────────┐
│ Topbar …                                                      │
├───────────────┬───────────────────────────────────────────────┤
│ Sidebar …     │ Project: Acme Site Revamp                     │
│               │ ┌─────────────Tabs─────────────────────────┐  │
│               │ │ Overview | Tasks | Files | Activity | AI │  │
│               │ └──────────────────────────────────────────┘  │
│               │ Overview                                      │
│               │  - Description                                │
│               │  - Members [Add]                              │
│               │  - Quick links                                │
│               │                                                │
│               │ Tasks (kanban/list toggle)                     │
│               │  [ + New Task ]   [Filter] [Sort] [Group]      │
│               │  ┌ To Do ─────┐  ┌ In Progress ──┐  ┌ Done ──┐ │
│               │  │ Task A     │  │ Task C        │  │ Task D  │ │
│               │  │ Task B     │  │               │  │         │ │
│               │  └────────────┘  └───────────────┘  └─────────┘ │
│               │                                                │
│               │ Right rail: Chat / AI assistant / Presence     │
└───────────────┴───────────────────────────────────────────────┘
```

#### D) Files

```
┌──────────────────────────────────────────────────────────────┐
│ Topbar …                                                      │
├───────────────┬───────────────────────────────────────────────┤
│ Sidebar …     │ Files                                         │
│               │ [ Upload ]  [ New Folder ]  [Search]          │
│               │ ┌────────────Grid of files──────────────────┐ │
│               │ │  📄 brief.pdf   🖼 hero.png   📁 Assets   │ │
│               │ └───────────────────────────────────────────┘ │
└───────────────┴───────────────────────────────────────────────┘
```

---

## 3) High‑Level Navigation Map

- **/admin**

  - Dashboard
  - Users
  - Queues/Jobs (Horizon)
  - Logs
  - Settings

- **/workspace**
  - Home (stats + activity)
  - Projects (list → detail)
  - Tasks (global view)
  - Files
  - Activity stream
  - Profile / Account

---

## 4) Auth & Session (Sanctum)

- Single cookie session for both surfaces.
- SPA flow: `GET /sanctum/csrf-cookie` → `POST /login` → cookies + `GET /api/me`.
- `.env` essentials:
  ```env
  SESSION_DOMAIN=.yourdomain.com
  SANCTUM_STATEFUL_DOMAINS=yourdomain.com,localhost,127.0.0.1
  ```
- CORS (if SPA served from a different origin): allow `supports_credentials` and specific dev origins.

---

## 5) API Contracts (MVP)

**Auth**

- `GET  /api/me` → `{ user, memberships, current_workspace }`
- `POST /login` / `POST /logout`

**Workspaces**

- `GET  /api/workspaces` (list I belong to)
- `GET  /api/workspaces/{id}`
- `POST /api/workspaces` (admin)

**Projects**

- `GET  /api/workspaces/{id}/projects`
- `POST /api/projects` (auto‑assign `workspace_id`)
- `GET  /api/projects/{id}`
- `PATCH/DELETE /api/projects/{id}`

**Tasks**

- `GET  /api/projects/{id}/tasks`
- `POST /api/tasks` (with `project_id`)
- `PATCH/DELETE /api/tasks/{id}`

**Files**

- `POST /api/uploads` (S3 pre‑signed)
- `GET  /api/projects/{id}/files`

> Enforce workspace scoping via **Policies** on each model.

---

## 6) Realtime

- **Provider:** Laravel Reverb (self‑host) or Pusher/Ably (hosted)
- **Channels:**
  - `presence.workspace.{workspaceId}` → online members
  - `project.{projectId}` → task/file/activity events
- **Events (examples):** `TaskCreated`, `TaskMoved`, `FileUploaded`, `MessagePosted`

---

## 7) Deployment Options

- **Simple:** Build SPA → dump assets into Laravel `public/workspace` and serve via `resources/views/workspace.blade.php` (fallback route).
- **Decoupled:** Host SPA on Vercel/CloudFront; Laravel API on Render/Fly; configure CORS + cookies.

---

## 8) MVP Build Checklist

**Backend**

- [ ] Sanctum configured; `SESSION_DOMAIN` and `SANCTUM_STATEFUL_DOMAINS` set
- [ ] Policies lock down workspace/project access
- [ ] Endpoints: `/api/me`, workspaces, projects, tasks
- [ ] S3 storage + pre‑signed uploads (dev: local driver)
- [ ] Redis + Horizon for queues

**Admin (Filament)**

- [ ] Users/Projects/Tasks/File resources
- [ ] Horizon link and basic log viewer
- [ ] Minimal/no custom styling

**Workspace (SPA)**

- [ ] React + Vite + Tailwind + shadcn scaffold
- [ ] Auth: csrf cookie + login/logout helpers
- [ ] Pages: Home, Projects (list/detail), Tasks, Files
- [ ] Realtime client connected to Reverb/Pusher

---

## 9) Implementation Notes & Gotchas

- Keep **one cookie** world. If SPA on a different subdomain, set proper cookie domain and CORS with `supports_credentials`.
- Always scope queries by membership/policies (never trust client workspace IDs).
- Prefer **optimistic UI** in SPA; confirm via realtime events.
- Use **feature flags** to gradually ship modules (files/chat/AI).
- Use **.env** to toggle Reverb vs. Pusher without code changes.

---

## 10) Roadmap (Phase 1 → Phase 3)

- **Phase 1 (MVP):** Auth, projects, tasks (kanban/list), files upload, activity feed.
- **Phase 2:** Mentions, notifications, presence, basic AI assistance.
- **Phase 3:** Desktop (Tauri), iPad PWA polish, offline caching, advanced AI flows.

---

## 11) Appendix: Route Stubs

```php
// routes/web.php
Route::view('/workspace/{any?}', 'workspace')->where('any', '.*');
// Filament registers /admin automatically

// routes/api.php (examples)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', MeController::class);

    Route::get('/workspaces', [WorkspaceController::class, 'index']);
    Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show']);
    Route::post('/workspaces', [WorkspaceController::class, 'store']);

    Route::get('/workspaces/{workspace}/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::patch('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
});
```

```tsx
// apps/workspace/src/lib/api.ts
import axios from "axios";
export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
});
```

---

**End of package.**
