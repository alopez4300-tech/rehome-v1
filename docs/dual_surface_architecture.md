# Dual-Surface Architecture Specification
## System Admin + App Platform

**Version:** 1.0
**Last Updated:** September 29, 2025
**Status:** Implementation Ready

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Architecture Overview](#architecture-overview)
3. [Naming Conventions](#naming-conventions)
4. [URL Structure](#url-structure)
5. [Tech Stack](#tech-stack)
6. [Data Models](#data-models)
7. [Authentication & Authorization](#authentication--authorization)
8. [API Contracts](#api-contracts)
9. [Real-Time Features](#real-time-features)
10. [Implementation Phases](#implementation-phases)
11. [Deployment Strategies](#deployment-strategies)
12. [UI/UX Guidelines](#uiux-guidelines)

---

## Executive Summary

This document defines a **dual-surface architecture** for a SaaS platform that separates internal operations from customer-facing functionality. The system provides:

- **System Admin** (`/admin`) — Internal operations panel built with Laravel Filament
- **App** (`/app`) — Customer-facing workspace built as a React SPA
- **Shared Backend** — Single Laravel 11 application with unified authentication via Sanctum
- **Real-Time Collaboration** — WebSocket-powered live updates using Laravel Reverb

### Key Principles

1. **Clear separation:** Internal ops vs. customer experience
2. **API-first:** SPA consumes RESTful API with workspace scoping
3. **Real-time:** WebSockets for live collaboration and updates
4. **Scalable:** Monorepo structure ready for desktop/mobile expansion
5. **Secure:** Row-level security with Laravel policies and Sanctum

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                         Frontend Layer                       │
├──────────────────────────┬──────────────────────────────────┤
│   System Admin (/admin)  │        App (/app)                │
│   - Laravel Filament     │   - React 18 + TypeScript        │
│   - Stock UI             │   - Vite + Tailwind CSS          │
│   - Internal team only   │   - shadcn/ui components         │
│                          │   - Customer-facing              │
└──────────────────────────┴──────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      API Layer (Laravel 11)                  │
│   - Sanctum Authentication (cookie-based)                   │
│   - RESTful API endpoints                                   │
│   - Policy-based authorization                              │
│   - Workspace scoping middleware                            │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Real-Time Layer                           │
│   - Laravel Reverb (WebSocket server)                       │
│   - Presence channels for online users                      │
│   - Private channels for workspace events                   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Data Layer (PostgreSQL)                   │
│   - Multi-tenant via workspace_id scoping                   │
│   - Row-level security enforced by policies                 │
│   - Redis for sessions, cache, queues                       │
└─────────────────────────────────────────────────────────────┘
```

---

## Naming Conventions

### Terminology

| Term | Definition | Used In |
|------|------------|---------|
| **System Admin** | Internal operations panel | Filament UI at `/admin` |
| **App** | Customer-facing workspace | React SPA at `/app` |
| **Workspace** | Container for projects, members, data | Core entity in App |
| **Workspace Owner** | User with full workspace permissions | App settings |
| **Workspace Settings** | Configuration area for workspace | App navigation (NOT "admin") |
| **Member** | User belonging to one or more workspaces | App context |

### Never Use These Terms in Customer-Facing UI

❌ "Admin Panel" (ambiguous)
❌ "Workspace Admin" (confusing)
❌ "Super Admin" (internal only)

### Instead Use

✅ "Settings"
✅ "Workspace Settings"
✅ "Workspace Owner"
✅ "Members & Roles"

---

## URL Structure

### System Admin (Internal)

```
/admin                          # Dashboard
/admin/users                    # User management
/admin/workspaces              # Workspace overview (read-only)
/admin/horizon                 # Job queue monitoring
/admin/logs                    # Application logs
/admin/feature-flags          # Feature toggles
/admin/billing                # System-wide billing overview
```

### App (Customer-Facing)

```
/app                                    # Landing/workspace switcher
/app/w/:workspaceSlug                  # Workspace home
/app/w/:workspaceSlug/projects         # Projects list
/app/w/:workspaceSlug/p/:projectId     # Project detail
/app/w/:workspaceSlug/tasks            # Tasks kanban
/app/w/:workspaceSlug/files            # File manager
/app/w/:workspaceSlug/activity         # Activity feed
/app/w/:workspaceSlug/settings         # Workspace settings

# Settings sub-routes
/app/w/:workspaceSlug/settings/general
/app/w/:workspaceSlug/settings/members
/app/w/:workspaceSlug/settings/billing
/app/w/:workspaceSlug/settings/integrations
/app/w/:workspaceSlug/settings/danger-zone
```

### Authentication Routes

```
/login                         # Shared login (redirects to /app or /admin)
/register                      # New user registration
/forgot-password              # Password reset
/api/*                        # API endpoints (Sanctum protected)
```

---

## Tech Stack

### Backend

| Component | Technology | Purpose |
|-----------|-----------|---------|
| Framework | Laravel 11 | API & admin backend |
| Database | PostgreSQL | Primary data store |
| Cache/Queue | Redis | Sessions, cache, job queues |
| Auth | Laravel Sanctum | Cookie-based SPA auth |
| Jobs | Laravel Horizon | Queue monitoring |
| WebSockets | Laravel Reverb | Real-time events |
| Admin Panel | Filament v3 | Internal operations UI |

### Frontend (App)

| Component | Technology | Purpose |
|-----------|-----------|---------|
| Framework | React 18 + TypeScript | UI framework |
| Build Tool | Vite | Fast dev server & builds |
| Styling | Tailwind CSS | Utility-first CSS |
| Components | shadcn/ui | Accessible component library |
| Icons | Lucide React | Icon system |
| HTTP Client | Axios | API communication |
| WebSockets | Laravel Echo + Pusher | Real-time client |
| State | React Context + Hooks | Local state management |
| Routing | React Router v6 | Client-side routing |

### Development Tools

- **Monorepo:** Turborepo or pnpm workspaces
- **Linting:** ESLint + Prettier
- **Type Checking:** TypeScript strict mode
- **Testing:** Pest (PHP) + Vitest (React)

---

## Data Models

### Core Entities

```
┌──────────────┐
│    User      │
├──────────────┤
│ id           │
│ name         │
│ email        │
│ password     │
│ workspace_id │ ← Default workspace
│ role         │ ← Global role (user/staff/super_admin)
└──────────────┘
       │
       │ belongs to
       ▼
┌──────────────┐
│  Workspace   │
├──────────────┤
│ id           │
│ name         │
│ slug         │
│ owner_id     │
│ settings     │ (JSON)
└──────────────┘
       │
       │ has many
       ▼
┌──────────────┐       ┌──────────────┐
│   Project    │       │WorkspaceMember│
├──────────────┤       ├──────────────┤
│ id           │       │ workspace_id │
│ workspace_id │       │ user_id      │
│ name         │       │ role         │ ← owner/admin/member/guest
│ description  │       │ permissions  │ (JSON)
│ status       │       └──────────────┘
│ settings     │ (JSON)
└──────────────┘
       │
       │ has many
       ▼
┌──────────────┐
│     Task     │
├──────────────┤
│ id           │
│ project_id   │
│ title        │
│ description  │
│ status       │ ← todo/in_progress/review/done
│ priority     │ ← low/medium/high
│ assigned_to  │
│ due_date     │
│ position     │ ← for kanban ordering
└──────────────┘
```

### Supporting Entities

```
┌──────────────┐
│     File     │
├──────────────┤
│ id           │
│ workspace_id │
│ project_id   │ (nullable)
│ name         │
│ path         │
│ size         │
│ mime_type    │
│ uploaded_by  │
└──────────────┘

┌──────────────┐
│   Activity   │
├──────────────┤
│ id           │
│ workspace_id │
│ user_id      │
│ subject_type │ ← polymorphic
│ subject_id   │
│ action       │ ← created/updated/deleted
│ metadata     │ (JSON)
└──────────────┘

┌──────────────┐
│   Comment    │
├──────────────┤
│ id           │
│ commentable_type │ ← polymorphic
│ commentable_id   │
│ user_id      │
│ body         │
│ mentions     │ (JSON array of user_ids)
└──────────────┘
```

### Database Schema Rules

1. **Every workspace-scoped table** must have `workspace_id` column
2. **Soft deletes** on all user-generated content
3. **Timestamps** (`created_at`, `updated_at`) on all tables
4. **UUID primary keys** for external-facing IDs
5. **Indexes** on foreign keys and frequently queried columns

---

## Authentication & Authorization

### Authentication Flow (Sanctum)

```
┌─────────┐                                    ┌─────────┐
│         │  1. GET /sanctum/csrf-cookie       │         │
│  React  │──────────────────────────────────▶│ Laravel │
│   SPA   │                                    │         │
│         │  2. POST /login (email, password)  │         │
│         │──────────────────────────────────▶│         │
│         │                                    │         │
│         │  3. Session cookie + CSRF token    │         │
│         │◀──────────────────────────────────│         │
│         │                                    │         │
│         │  4. GET /api/me (with cookies)     │         │
│         │──────────────────────────────────▶│         │
│         │                                    │         │
│         │  5. User + workspace data          │         │
│         │◀──────────────────────────────────│         │
└─────────┘                                    └─────────┘
```

### Environment Configuration

```env
# Laravel .env
SESSION_DOMAIN=.yourdomain.com
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,app.yourdomain.com,localhost:5173
SESSION_DRIVER=redis
SESSION_LIFETIME=120
```

### Axios Configuration (SPA)

```typescript
// src/lib/api.ts
import axios from 'axios';

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL, // http://localhost:8000
  withCredentials: true, // CRITICAL: sends cookies
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
});
```

### Authorization Layers

#### 1. Route Middleware

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'workspace.scope'])->group(function () {
    // All routes automatically scoped to user's workspace
});
```

#### 2. Policies (Laravel)

```php
// app/Policies/ProjectPolicy.php
class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $user->workspace_id === $project->workspace_id;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->workspace_id === $project->workspace_id
            && $user->hasWorkspacePermission('projects.update');
    }
}
```

#### 3. Query Scopes

```php
// app/Models/Project.php
protected static function booted()
{
    static::addGlobalScope('workspace', function (Builder $query) {
        if (auth()->check()) {
            $query->where('workspace_id', auth()->user()->workspace_id);
        }
    });
}
```

### Role-Based Access Control

#### Global Roles (User Model)

- `super_admin` — Full system access (System Admin only)
- `staff` — Read-only access to System Admin
- `user` — Standard user (App only)

#### Workspace Roles (WorkspaceMember)

- `owner` — Full workspace control + billing
- `admin` — Manage members, projects, settings
- `member` — Create/edit own content
- `guest` — Read-only access

#### Permission Structure (JSON)

```json
{
  "projects": {
    "create": true,
    "update": true,
    "delete": false
  },
  "members": {
    "invite": false,
    "remove": false
  },
  "settings": {
    "update": false
  }
}
```

---

## API Contracts

### Authentication Endpoints

```
POST   /login                    # Email/password login
POST   /logout                   # Invalidate session
GET    /sanctum/csrf-cookie     # Get CSRF token
POST   /register                # New user registration
POST   /forgot-password         # Request reset link
POST   /reset-password          # Reset with token
```

### Core API Endpoints

#### User & Workspace

```
GET    /api/me                           # Current user + workspace
GET    /api/workspaces                   # User's workspaces (if multi-workspace)
GET    /api/workspaces/:id               # Workspace details
PATCH  /api/workspaces/:id               # Update workspace
DELETE /api/workspaces/:id               # Delete workspace (owner only)
```

#### Projects

```
GET    /api/projects                     # List projects (workspace-scoped)
POST   /api/projects                     # Create project
GET    /api/projects/:id                 # Project details
PATCH  /api/projects/:id                 # Update project
DELETE /api/projects/:id                 # Delete project
POST   /api/projects/:id/archive         # Archive project
POST   /api/projects/:id/restore         # Restore project
```

#### Tasks

```
GET    /api/projects/:projectId/tasks    # List tasks
POST   /api/projects/:projectId/tasks    # Create task
GET    /api/tasks/:id                    # Task details
PATCH  /api/tasks/:id                    # Update task
DELETE /api/tasks/:id                    # Delete task
PATCH  /api/tasks/:id/move               # Move task (kanban)
POST   /api/tasks/:id/assign             # Assign to user
```

#### Files

```
GET    /api/files                        # List files (workspace-scoped)
POST   /api/files                        # Upload file
GET    /api/files/:id                    # File details
GET    /api/files/:id/download           # Download file
DELETE /api/files/:id                    # Delete file
PATCH  /api/files/:id                    # Rename/move file
```

#### Activity

```
GET    /api/activity                     # Workspace activity feed
GET    /api/projects/:id/activity        # Project activity
GET    /api/tasks/:id/activity           # Task activity
```

#### Members

```
GET    /api/workspaces/:id/members       # List members
POST   /api/workspaces/:id/members       # Invite member
PATCH  /api/members/:id                  # Update role/permissions
DELETE /api/members/:id                  # Remove member
```

### Request/Response Format

#### Standard Response

```json
{
  "data": { ... },
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 150
  }
}
```

#### Error Response

```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### Pagination

All list endpoints support:
- `?page=1`
- `?per_page=20` (max 100)
- `?sort=created_at`
- `?order=desc`

### Filtering

```
GET /api/projects?status=active&search=website
GET /api/tasks?assigned_to=123&priority=high
```

---

## Real-Time Features

### Laravel Reverb Configuration

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=local
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Channel Types

#### Private Channels (Workspace-Scoped)

```php
// routes/channels.php
Broadcast::channel('workspace.{workspaceId}', function ($user, $workspaceId) {
    return $user->workspace_id == $workspaceId;
});

Broadcast::channel('project.{projectId}', function ($user, $projectId) {
    return $user->can('view', Project::find($projectId));
});
```

#### Presence Channels (Online Users)

```php
Broadcast::channel('presence.workspace.{workspaceId}', function ($user, $workspaceId) {
    if ($user->workspace_id == $workspaceId) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar_url,
        ];
    }
});
```

### Broadcast Events

```php
// app/Events/TaskUpdated.php
class TaskUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Task $task) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('project.' . $this->task->project_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'task' => $this->task->load('assignee'),
            'user' => auth()->user(),
        ];
    }
}
```

### Echo Client Setup (React)

```typescript
// src/lib/echo.ts
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

export const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
  wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
  enabledTransports: ['ws', 'wss'],
});
```

### Real-Time Features to Implement

1. **Live Task Updates** — Kanban board auto-refreshes
2. **Online Presence** — Show who's viewing a project
3. **Activity Feed** — Real-time activity stream
4. **Notifications** — Toast notifications for mentions/assignments
5. **Typing Indicators** — Show when users are typing comments
6. **File Upload Progress** — Live upload status

---

## Implementation Phases

### Phase 1: Backend Foundation (Week 1)

#### 1.1 Sanctum Configuration
- [ ] Update `.env` with session domain and stateful domains
- [ ] Configure CORS in `config/cors.php`
- [ ] Test CSRF cookie endpoint

#### 1.2 Database Schema
- [ ] Create `workspaces` migration
- [ ] Create `workspace_members` migration
- [ ] Create `projects` migration
- [ ] Create `tasks` migration
- [ ] Create `files` migration
- [ ] Create `activities` migration
- [ ] Run migrations

#### 1.3 Models & Relationships
- [ ] `Workspace` model with relationships
- [ ] `Project` model with workspace scope
- [ ] `Task` model with project relationship
- [ ] `File` model with polymorphic relationships
- [ ] `Activity` model with polymorphic subject

#### 1.4 Authorization
- [ ] `WorkspacePolicy` with view/update/delete
- [ ] `ProjectPolicy` with CRUD permissions
- [ ] `TaskPolicy` with workspace scoping
- [ ] Global scope for workspace filtering
- [ ] Middleware for workspace context

#### 1.5 API Controllers
- [ ] `MeController` — Current user endpoint
- [ ] `WorkspaceController` — CRUD operations
- [ ] `ProjectController` — CRUD + archive
- [ ] `TaskController` — CRUD + move/assign
- [ ] `FileController` — Upload/download/delete
- [ ] `ActivityController` — Feed endpoint

#### 1.6 Testing
- [ ] Feature tests for auth flow
- [ ] Policy tests for workspace scoping
- [ ] API tests for CRUD operations

---

### Phase 2: System Admin (Filament) (Week 2)

#### 2.1 Filament Resources
- [ ] `UserResource` — View/edit users
- [ ] `WorkspaceResource` — Read-only workspace overview
- [ ] `ProjectResource` — Read-only project list
- [ ] `ActivityResource` — System-wide activity log

#### 2.2 Navigation
- [ ] Dashboard with stats
- [ ] Link to Laravel Horizon
- [ ] Link to Laravel Telescope (if enabled)
- [ ] Feature flags page (optional)

#### 2.3 Customization
- [ ] Keep stock Filament theme
- [ ] Add internal branding (logo/colors)
- [ ] Configure user menu

---

### Phase 3: React SPA Scaffold (Week 3)

#### 3.1 Project Setup
```bash
mkdir -p apps/app
cd apps/app
npm create vite@latest . -- --template react-ts
npm install
```

#### 3.2 Dependencies
```bash
# Styling
npm install -D tailwindcss postcss autoprefixer
npm install class-variance-authority clsx tailwind-merge

# UI Components
npm install @radix-ui/react-slot lucide-react

# API & Real-time
npm install axios laravel-echo pusher-js

# Routing
npm install react-router-dom
```

#### 3.3 Folder Structure
```
apps/app/src/
├── components/
│   ├── ui/              # shadcn components
│   └── layout/          # Sidebar, Topbar, Container
├── features/
│   ├── auth/           # Login, Register
│   ├── projects/       # Project list, detail
│   ├── tasks/          # Kanban board
│   └── settings/       # Workspace settings
├── lib/
│   ├── api.ts          # Axios client
│   ├── echo.ts         # Laravel Echo
│   └── utils.ts        # Helpers
├── hooks/
│   ├── useAuth.ts      # Auth context
│   ├── useProjects.ts  # Project queries
│   └── useTasks.ts     # Task queries
├── types/
│   └── index.ts        # TypeScript definitions
└── App.tsx
```

#### 3.4 Authentication
- [ ] Login page with email/password
- [ ] Auth context provider
- [ ] Protected route wrapper
- [ ] Logout functionality
- [ ] Session persistence

#### 3.5 Core Pages
- [ ] Workspace dashboard (home)
- [ ] Projects list + create
- [ ] Project detail view
- [ ] Tasks kanban board
- [ ] Workspace settings

---

### Phase 4: Real-Time Integration (Week 4)

#### 4.1 Laravel Reverb
- [ ] Install and configure Reverb
- [ ] Define broadcast channels
- [ ] Create broadcast events
- [ ] Test with Tinker

#### 4.2 React Echo Integration
- [ ] Connect to Reverb on app load
- [ ] Subscribe to workspace channel
- [ ] Handle incoming events
- [ ] Update UI reactively

#### 4.3 Live Features
- [ ] Real-time task updates
- [ ] Online presence indicators
- [ ] Activity feed streaming
- [ ] Toast notifications

---

### Phase 5: Polish & Launch (Week 5-6)

#### 5.1 UI/UX
- [ ] Responsive design (mobile/tablet)
- [ ] Loading states
- [ ] Error boundaries
- [ ] Empty states
- [ ] Keyboard shortcuts

#### 5.2 Performance
- [ ] API response caching
- [ ] Optimistic UI updates
- [ ] Lazy loading routes
- [ ] Image optimization

#### 5.3 Security
- [ ] Rate limiting on API
- [ ] CSRF protection verified
- [ ] SQL injection prevention
- [ ] XSS sanitization

#### 5.4 Deployment
- [ ] Environment configuration
- [ ] CI/CD pipeline
- [ ] Database backups
- [ ] Monitoring setup

---

## Deployment Strategies

### Option A: Monolithic (Simple)

**Structure:**
```
Laravel app
├── public/
│   └── build/         # Compiled SPA assets
├── resources/
│   └── views/
│       └── app.blade.php   # SPA entry point
└── apps/
    └── app/           # React source
```

**Build Process:**
```bash
cd apps/app
npm run build
# Output to public/build
```

**Laravel Route:**
```php
Route::view('/app/{any?}', 'app')->where('any', '.*');
```

**Pros:**
- Single deployment
- Same domain (no CORS issues)
- Simple hosting

**Cons:**
- Slower static asset delivery
- Laravel serves all requests

---

### Option B: Decoupled (Scalable)

**Structure:**
```
API (api.yourdomain.com)
  └── Laravel backend only

App (app.yourdomain.com)
  └── Static SPA on S3/Cloudflare/Vercel
```

**Deployment:**
- Backend: Laravel Forge or AWS ECS
- Frontend: Vercel, Cloudflare Pages, or S3 + CloudFront

**Pros:**
- CDN for static assets
- Independent scaling
- Faster load times

**Cons:**
- CORS configuration required
- Cookie domain setup
- More complex deployment

---

### Recommended: Option A for MVP, Option B for Scale

Start with monolithic, migrate to decoupled once you hit traffic constraints.

---

## UI/UX Guidelines

### Design System

#### Colors (Tailwind)

```js
// tailwind.config.js
theme: {
  extend: {
    colors: {
      brand: {
        50: '#...',
        500: '#...',
        900: '#...',
      }
    }
  }
}
```

#### Typography

- **Headings:** Font size scale (text-2xl, text-xl, text-lg)
- **Body:** text-base with text-gray-700
- **Labels:** text-sm font-medium text-gray-900

#### Spacing

- **Page padding:** p-6 or p-8
- **Card padding:** p-4
- **Section gaps:** space-y-6

---

### Navigation Structure

#### App Topbar

```
┌─────────────────────────────────────────────────────────┐
│ [≡ Sidebar]  Workspace: Acme Corp ▾   [⌘K Search]  [👤]│
└─────────────────────────────────────────────────────────┘
```

#### App Sidebar

```
┌──────────────┐
│ Home         │
│ Projects     │
│ Tasks        │
│ Files        │
│ Activity     │
│ ────────     │
│ Settings     │
└──────────────┘
```

#### Workspace Settings Tabs

```
General | Members & Roles | Billing | Integrations | Danger Zone
```

---

### Component Patterns

#### Empty States

```tsx
<div className="text-center py-12">
  <Icon className="mx-auto h-12 w-12 text-gray-400" />
  <h3 className="mt-2 text-sm font-semibold">No projects</h3>
  <p className="mt-1 text-sm text-gray-500">
    Get started by creating a new project.
  </p>
  <Button className="mt-6">New Project</Button>
</div>
```

#### Loading States

```tsx
<div className="animate-pulse space-y-4">
  <div className="h-4 bg-gray-200 rounded w-3/4"></div>
  <div className="h-4 bg-gray-200 rounded w-1/2"></div>
</div>
```

#### Error States

```tsx
<Alert variant="destructive">
  <AlertCircle className="h-4 w-4" />
  <AlertTitle>Error</AlertTitle>
  <AlertDescription>
    Failed to load projects. Please try again.
  </AlertDescription>
</Alert>
```

---

### Accessibility Checklist

- [ ] Keyboard navigation (Tab, Enter, Escape)
- [ ] Screen reader labels (aria-label)
- [ ] Focus indicators (focus-visible:ring)
- [ ] Color contrast WCAG AA
- [ ] Skip to main content link

---

## Appendix

### Environment Variables

#### Backend (.env)

```env
APP_NAME=YourApp
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=yourapp
DB_USERNAME=postgres
DB_PASSWORD=secret

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Sanctum
SESSION_DOMAIN=.yourdomain.com
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,app.yourdomain.com,localhost:5173
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Broadcasting
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=local
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
```

#### Frontend (.env)

```env
VITE_API_URL=http://localhost:8000
VITE_REVERB_APP_KEY=your-app-key
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

---

### Database Migrations

#### Create Workspaces Table

```php
// database/migrations/2024_01_01_000001_create_workspaces_table.php
Schema::create('workspaces', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
    $table->json('settings')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

#### Create Workspace Members Table

```php
// database/migrations/2024_01_01_000002_create_workspace_members_table.php
Schema::create('workspace_members', function (Blueprint $table) {
    $table->id();
    $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->enum('role', ['owner', 'admin', 'member', 'guest'])->default('member');
    $table->json('permissions')->nullable();
    $table->timestamp('joined_at')->useCurrent();
    $table->timestamps();

    $table->unique(['workspace_id', 'user_id']);
});
```

#### Create Projects Table

```php
// database/migrations/2024_01_01_000003_create_projects_table.php
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->text('description')->nullable();
    $table->enum('status', ['active', 'archived', 'completed'])->default('active');
    $table->json('settings')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['workspace_id', 'status']);
});
```

#### Create Tasks Table

```php
// database/migrations/2024_01_01_000004_create_tasks_table.php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->onDelete('cascade');
    $table->string('title');
    $table->text('description')->nullable();
    $table->enum('status', ['todo', 'in_progress', 'review', 'done'])->default('todo');
    $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
    $table->foreignId('assigned_to')->nullable()->constrained('users');
    $table->date('due_date')->nullable();
    $table->integer('position')->default(0);
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['project_id', 'status']);
    $table->index('assigned_to');
});
```

#### Create Files Table

```php
// database/migrations/2024_01_01_000005_create_files_table.php
Schema::create('files', function (Blueprint $table) {
    $table->id();
    $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
    $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('path');
    $table->bigInteger('size'); // bytes
    $table->string('mime_type');
    $table->foreignId('uploaded_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['workspace_id', 'project_id']);
});
```

#### Create Activities Table

```php
// database/migrations/2024_01_01_000006_create_activities_table.php
Schema::create('activities', function (Blueprint $table) {
    $table->id();
    $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->morphs('subject'); // subject_type, subject_id
    $table->string('action'); // created, updated, deleted, etc.
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->index(['workspace_id', 'created_at']);
});
```

#### Add Workspace ID to Users

```php
// database/migrations/2024_01_01_000007_add_workspace_to_users.php
Schema::table('users', function (Blueprint $table) {
    $table->foreignId('workspace_id')->nullable()->constrained()->onDelete('set null');
    $table->enum('role', ['user', 'staff', 'super_admin'])->default('user');
});
```

---

### Code Examples

#### Workspace Scope Middleware

```php
// app/Http/Middleware/EnsureWorkspaceScope.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureWorkspaceScope
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!auth()->user()->workspace_id) {
            return response()->json([
                'message' => 'No workspace assigned.'
            ], 403);
        }

        // Set workspace context for queries
        app()->instance('current_workspace_id', auth()->user()->workspace_id);

        return $next($request);
    }
}
```

#### Project Controller

```php
// app/Http/Controllers/Api/ProjectController.php
namespace App\Http\Controllers\Api;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::query()
            ->with('creator')
            ->latest()
            ->paginate(20);

        return response()->json($projects);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,archived,completed',
        ]);

        $project = Project::create([
            ...$validated,
            'workspace_id' => auth()->user()->workspace_id,
            'created_by' => auth()->id(),
        ]);

        return response()->json($project->load('creator'), 201);
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        return response()->json(
            $project->load(['creator', 'tasks.assignee'])
        );
    }

    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,archived,completed',
        ]);

        $project->update($validated);

        return response()->json($project);
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json(['message' => 'Project deleted.']);
    }
}
```

#### Project Model with Global Scope

```php
// app/Models/Project.php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'name',
        'description',
        'status',
        'settings',
        'created_by',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    protected static function booted()
    {
        // Global scope: only show projects from user's workspace
        static::addGlobalScope('workspace', function (Builder $query) {
            if (auth()->check() && auth()->user()->workspace_id) {
                $query->where('workspace_id', auth()->user()->workspace_id);
            }
        });
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject');
    }
}
```

#### Project Policy

```php
// app/Policies/ProjectPolicy.php
namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->workspace_id !== null;
    }

    public function view(User $user, Project $project): bool
    {
        return $user->workspace_id === $project->workspace_id;
    }

    public function create(User $user): bool
    {
        return $user->workspace_id !== null
            && $user->hasWorkspacePermission('projects.create');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->workspace_id === $project->workspace_id
            && $user->hasWorkspacePermission('projects.update');
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->workspace_id === $project->workspace_id
            && $user->hasWorkspacePermission('projects.delete');
    }
}
```

#### React Auth Hook

```typescript
// apps/app/src/hooks/useAuth.ts
import { createContext, useContext, useState, useEffect } from 'react';
import { api } from '@/lib/api';

interface User {
  id: number;
  name: string;
  email: string;
  workspace_id: number;
  workspace?: {
    id: number;
    name: string;
    slug: string;
  };
}

interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  refetch: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  const fetchUser = async () => {
    try {
      const { data } = await api.get('/api/me');
      setUser(data);
    } catch (error) {
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  const login = async (email: string, password: string) => {
    await api.get('/sanctum/csrf-cookie');
    await api.post('/login', { email, password });
    await fetchUser();
  };

  const logout = async () => {
    await api.post('/logout');
    setUser(null);
  };

  useEffect(() => {
    fetchUser();
  }, []);

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, refetch: fetchUser }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
```

#### React Projects Hook

```typescript
// apps/app/src/hooks/useProjects.ts
import { useState, useEffect } from 'react';
import { api } from '@/lib/api';

interface Project {
  id: number;
  name: string;
  description?: string;
  status: 'active' | 'archived' | 'completed';
  created_at: string;
}

export function useProjects() {
  const [projects, setProjects] = useState<Project[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchProjects = async () => {
    try {
      setLoading(true);
      const { data } = await api.get('/api/projects');
      setProjects(data.data);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const createProject = async (project: { name: string; description?: string }) => {
    const { data } = await api.post('/api/projects', project);
    setProjects((prev) => [data, ...prev]);
    return data;
  };

  const updateProject = async (id: number, updates: Partial<Project>) => {
    const { data } = await api.patch(`/api/projects/${id}`, updates);
    setProjects((prev) => prev.map((p) => (p.id === id ? data : p)));
    return data;
  };

  const deleteProject = async (id: number) => {
    await api.delete(`/api/projects/${id}`);
    setProjects((prev) => prev.filter((p) => p.id !== id));
  };

  useEffect(() => {
    fetchProjects();
  }, []);

  return {
    projects,
    loading,
    error,
    refetch: fetchProjects,
    createProject,
    updateProject,
    deleteProject,
  };
}
```

#### React Login Page

```tsx
// apps/app/src/pages/Login.tsx
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await login(email, password);
      navigate('/app');
    } catch (err: any) {
      setError(err.response?.data?.message || 'Login failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50">
      <div className="w-full max-w-md space-y-8 rounded-lg bg-white p-8 shadow">
        <div>
          <h2 className="text-center text-3xl font-bold">Sign in to your account</h2>
        </div>
        <form onSubmit={handleSubmit} className="space-y-6">
          <div>
            <Label htmlFor="email">Email address</Label>
            <Input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              className="mt-1"
            />
          </div>
          <div>
            <Label htmlFor="password">Password</Label>
            <Input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              className="mt-1"
            />
          </div>
          {error && (
            <div className="rounded-md bg-red-50 p-4 text-sm text-red-800">
              {error}
            </div>
          )}
          <Button type="submit" className="w-full" disabled={loading}>
            {loading ? 'Signing in...' : 'Sign in'}
          </Button>
        </form>
      </div>
    </div>
  );
}
```

---

## Testing Strategy

### Backend Tests

#### Feature Test Example

```php
// tests/Feature/ProjectTest.php
namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    public function test_user_can_list_their_workspace_projects()
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create(['workspace_id' => $workspace->id]);
        $projects = Project::factory()->count(3)->create([
            'workspace_id' => $workspace->id
        ]);

        $response = $this->actingAs($user)->getJson('/api/projects');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_cannot_see_other_workspace_projects()
    {
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();

        $user = User::factory()->create(['workspace_id' => $workspace1->id]);

        Project::factory()->create(['workspace_id' => $workspace1->id]);
        Project::factory()->create(['workspace_id' => $workspace2->id]);

        $response = $this->actingAs($user)->getJson('/api/projects');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
```

### Frontend Tests

#### Component Test Example

```typescript
// apps/app/src/components/ProjectCard.test.tsx
import { render, screen } from '@testing-library/react';
import { ProjectCard } from './ProjectCard';

describe('ProjectCard', () => {
  const mockProject = {
    id: 1,
    name: 'Test Project',
    description: 'Test description',
    status: 'active' as const,
    created_at: '2024-01-01T00:00:00.000Z',
  };

  it('renders project name', () => {
    render(<ProjectCard project={mockProject} />);
    expect(screen.getByText('Test Project')).toBeInTheDocument();
  });

  it('renders project description', () => {
    render(<ProjectCard project={mockProject} />);
    expect(screen.getByText('Test description')).toBeInTheDocument();
  });
});
```

---

## Performance Optimization

### Backend

1. **Database Indexing**
   - Index foreign keys (`workspace_id`, `project_id`)
   - Composite indexes on frequently queried columns
   - Index on `created_at` for activity feeds

2. **Query Optimization**
   - Use `select()` to limit columns
   - Eager load relationships with `with()`
   - Use `chunk()` for large datasets

3. **Caching Strategy**
   ```php
   Cache::remember("workspace.{$workspaceId}.projects", 3600, function () {
       return Project::with('tasks')->get();
   });
   ```

4. **Queue Jobs**
   - File processing
   - Email notifications
   - Report generation

### Frontend

1. **Code Splitting**
   ```typescript
   const ProjectDetail = lazy(() => import('./pages/ProjectDetail'));
   ```

2. **API Response Caching**
   ```typescript
   // Use React Query or SWR for automatic caching
   const { data } = useQuery(['projects'], fetchProjects);
   ```

3. **Image Optimization**
   - Lazy load images below the fold
   - Use WebP format with fallbacks
   - Responsive image sizes

4. **Bundle Optimization**
   - Tree shaking
   - Minification
   - Compression (gzip/brotli)

---

## Security Checklist

### Backend

- [x] CSRF protection enabled (Sanctum)
- [x] SQL injection prevention (Eloquent)
- [x] XSS protection (Blade escaping)
- [ ] Rate limiting on login/API endpoints
- [ ] Input validation on all requests
- [ ] File upload validation (type, size)
- [ ] HTTPS enforced in production
- [ ] Secure session configuration
- [ ] Password hashing (bcrypt)
- [ ] Environment variables secured

### Frontend

- [x] XSS prevention (React escaping)
- [ ] Content Security Policy headers
- [ ] HTTPS only (no mixed content)
- [ ] Secure cookie flags (httpOnly, secure, sameSite)
- [ ] Input sanitization
- [ ] Auth token storage (httpOnly cookies, not localStorage)
- [ ] CORS properly configured

---

## Monitoring & Logging

### Application Monitoring

1. **Laravel Telescope** (development)
   - Query logging
   - Request inspection
   - Exception tracking

2. **Laravel Horizon** (production)
   - Queue monitoring
   - Failed job alerts
   - Job metrics

3. **Error Tracking**
   - Sentry or Bugsnag integration
   - Real-time error alerts
   - Stack trace capture

### Performance Monitoring

1. **APM Tools**
   - New Relic
   - Datadog
   - Application Insights

2. **Database Monitoring**
   - Slow query log
   - Connection pool stats
   - Index usage

### User Analytics

1. **Frontend**
   - Page view tracking
   - User journey mapping
   - Feature usage metrics

2. **Backend**
   - API endpoint usage
   - Response times
   - Error rates

---

## Glossary

| Term | Definition |
|------|------------|
| **System Admin** | Internal operations panel (Filament) at `/admin` |
| **App** | Customer-facing workspace (React SPA) at `/app` |
| **Workspace** | Container for projects, members, and data |
| **Workspace Owner** | User with full workspace permissions including billing |
| **Member** | User belonging to a workspace with specific role |
| **Sanctum** | Laravel's SPA authentication system using cookies |
| **Reverb** | Laravel's WebSocket server for real-time features |
| **Policy** | Laravel authorization class for model permissions |
| **Global Scope** | Automatic query filter applied to all model queries |
| **Presence Channel** | WebSocket channel showing online users |
| **Private Channel** | WebSocket channel requiring authentication |

---

## Next Steps

1. **Review this specification** with your team
2. **Set up development environment** (Laravel + React)
3. **Begin Phase 1** (Backend foundation)
4. **Create initial migrations** and seed test data
5. **Build MVP features** following the implementation phases
6. **Deploy to staging** for internal testing
7. **Iterate based on feedback**
8. **Launch to production**

---

## Support & Resources

- **Laravel Documentation:** https://laravel.com/docs
- **React Documentation:** https://react.dev
- **Tailwind CSS:** https://tailwindcss.com/docs
- **shadcn/ui:** https://ui.shadcn.com
- **Filament:** https://filamentphp.com/docs
- **Laravel Reverb:** https://reverb.laravel.com

---

**Document Status:** Ready for Implementation
**Last Updated:** September 29, 2025
**Version:** 1.0
