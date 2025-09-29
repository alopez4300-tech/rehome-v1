# ReHome v1 — Stack & Operations (Render + S3 + Resend)

> A practical, production-minded overview of the full stack you're building.
> Hosting: **Render**. Storage: **S3**. Email: **Resend**. **No CloudFront.**

---

## Backend

- Language/runtime: **PHP 8.3 (FPM)** on Linux
- Framework: **Laravel 11.x**
- **App shape:** one Laravel instance serving **two surfaces**

  - **Filament (admins):** `/admin/*` – session auth
  - **API (team/consultant/client):** `/api/*` – Sanctum

- Admin panel: **Filament v3** (Forms, Tables, Resources, Pages)
- **Realtime:** Laravel Broadcasting (Echo) + **WebSockets**

  - Primary: **Laravel Reverb** on a Render web service or worker
  - Alternative: **Pusher**/**Ably**

- **Reactivity (admin):** Livewire v3 + Alpine.js (Filament)
- ORM: Eloquent + Migrations/Seeders/Factories
- Auth:

  - **Web (admins):** Session (Filament login). Breeze/Jetstream optional
  - **API (non-admins):** **Sanctum** (token/cookie)
  - SSO (optional): Socialite

- Validation/Serialization: Form Requests + **API Resources** (JSON)

### Jobs / Queues / Scheduler

- **Queues:** **Redis** (recommended from day one)
- Workers: **Horizon** (separate Render worker) with tags by workspace/project
- Cron: `php artisan schedule:run` every minute
- Retries/backoff: **exponential backoff**, **circuit-breaker** on provider failure, **dead-letter logging**
- Timeouts: per-run LLM timeout (`AI_TIMEOUT`, default **60s**)

### Caching / Sessions / Rate limit

- **Redis** for cache, sessions, rate limiting

### Database

- **SQLite (dev)** → **PostgreSQL 15+ (prod)** (or MySQL 8.x)
- UUIDs optional (`ramsey/uuid`)

### Files / Storage

- Local (dev)
- **S3 (prod)** via Flysystem; **signed URLs** for private objects
- Image/media: `intervention/image` **or** `spatie/laravel-medialibrary`
- Security: **file type allowlist** + optional AV scanning

  - AV options: ClamAV sidecar on Render **or** S3→Lambda workflow

### Mailing

- **Resend** (prod)
- **Mailpit/Mailhog** (dev)

### Search (optional)

- Meilisearch or Algolia (Scout)

### Observability

- Errors: Sentry or Bugsnag
- Debug (dev): Telescope
- Logs: Monolog JSON to stdout
- **Audit:** log high-value/admin agent queries & actions

### Security

- HTTPS, HSTS, secure cookies
- **CORS** for SPA origins (web + device + desktop)
- API rate limiting (general + **agent-specific**)
- CSRF for Filament (web)
- Prompt-injection mitigations: strict system prompts; no arbitrary tool exec; escape/strip injected instructions

### Domain add-ons

- Roles/Permissions: `spatie/laravel-permission`
- Activity log: `spatie/laravel-activitylog`
- Backups: `spatie/laravel-backup`

### Domain policies

- **Admins**: scoped by `workspace_id` (see only their workspace)
- **Non-admins**: scoped by **project assignments** (pivot `project_user`)
- Enforcement: API controllers, Broadcast channel auth (`routes/channels.php`), query scopes

### Realtime channels (examples)

- `private-projects.{projectId}` → TaskCreated/Updated/Moved, MessageSent, FileUploaded, **AgentMessageCreated**, **AgentSummaryReady**
- `presence-projects.{projectId}` → typing/online
- `private-users.{userId}` → per-user notifications

---

## AI Agent (backend service layer)

**Purpose:** Chat assistants that summarize/answer over project/workspace data

- **Participant agent:** project-only scope (team/consultant/client)
- **Admin agent:** workspace-wide scope (admin)

**Execution model:**
API → create `agent_run` → **Redis job** builds scoped context (no cross-project leakage) → LLM call (timeout-bounded) → persist `agent_messages` → broadcast token stream via Echo → finalize.
**Horizon tags:** `['agent', "workspace:{id}", "project:{id}"]`

**Streaming:** **Echo/WebSockets** for token streaming, typing indicators, cancel

**Automated summaries (scheduler):**

- Daily per-project digest (participant-visible)
- Weekly per-workspace rollup (admin-only)
- **Graceful degradation:** when budget exceeded, serve cached summaries

**Minimal data model (new tables):**

- `agent_threads` (id, project_id, created_by, audience enum('participant','admin'), title, status)
- `agent_messages` (id, thread_id, role enum('user','assistant','tool','system'), content markdown, meta JSON)
- `agent_runs` (id, thread_id, status, provider, model, tokens_in/out, cost_cents, **context_used JSON**, started_at, finished_at, error)
- _(Phase 2)_ `project_summaries` (id, project_id, type enum('daily','weekly','milestone','meeting'), content markdown, generated_at)
- _(Optional)_ `workspaces.ai_model`, `workspaces.ai_budget_cents`

**Context-building policy (fit to token budget):**

- **Split:** messages **50%**, tasks **30%**, files/meta **20%** (configurable)
- **Ordering:** newest first; prioritize **@mentions** & status changes
- **Truncation:** **drop whole items** oldest-first (avoid mid-message truncation)
- **Safety buffer:** ~10% tokens reserved for system/formatting
- **PII redaction:** mask sensitive fields unless role permits
- **Performance:** eager load to avoid N+1

**Agent events**

- `AgentMessageCreated`: `{ thread_id, message_id, role:'assistant', delta, done }`
- `AgentSummaryReady`: `{ project_id, type:'daily'|'weekly'|'meeting'|'milestone', content }`

**Cost/rate governance**

- Track **tokens + cost_cents** on `agent_runs`
- **Budgets:** per user/workspace daily + per-workspace monthly; warn & block/degrade on limit
- **Rate limits:** e.g., 5/min/user, 50/day/user, 500/day/workspace

**Provider/config**

- `.env`: `AI_PROVIDER`, `AI_MODEL`, `AI_TEMPERATURE`, `AI_MAX_TOKENS`, `AI_TIMEOUT`, `AI_TOKEN_SAFETY`
- `config/ai.php`:

  ```php
  return [
    'provider'   => env('AI_PROVIDER', 'anthropic'), // default: tone-first & cheap
    'model'      => env('AI_MODEL', 'claude-3-5-haiku-20241022'),
    'max_tokens' => (int) env('AI_MAX_TOKENS', 8192),
    'temperature'=> (float) env('AI_TEMPERATURE', 0.7),

    // Context policy
    'context_budget' => ['messages' => 0.5, 'tasks' => 0.3, 'files' => 0.2],
    'token_safety_buffer' => (float) env('AI_TOKEN_SAFETY', 0.10),
    'truncate_strategy' => 'drop_whole',

    // Governance
    'rate_limits' => ['per_user_minute' => 5, 'per_user_day' => 50, 'per_workspace_day' => 500],
    'timeout_seconds' => (int) env('AI_TIMEOUT', 60),

    // Costs (USD per 1M tokens)
    'costs' => [
      'claude-3-5-haiku-20241022' => ['input' => 1.00, 'output' => 5.00],
      'gpt-4o-mini'               => ['input' => 0.15, 'output' => 0.60], // fallback for strict JSON
    ],
  ];
  ```

> Default to **Claude 3.5 Haiku** (tone-friendly, long context, still cheap). Use **GPT-4o-mini** selectively when strict JSON/tooling is required.

---

## Frontend

### Admin (web + wrappers)

- Build tool: **Vite 5**
- CSS: **Tailwind 3** + PostCSS + Autoprefixer
- JS: Alpine.js 3 (Filament), Livewire morphdom
- Icons: Heroicons/Lucide
- Optional UI extras: daisyUI, Headless UI
- **Realtime:** Echo (WebSockets) in Filament assets
- **Desktop (Tauri)**: wrap `/admin` (Filament) as desktop app
- **iPad (PWA/Capacitor)**: lightweight shell loading `/admin` (works with Render backend & S3 assets)

**Admin agent UX**

- Filament Page per project: tabs **Chat**, **Daily Digest**, **Weekly Workspace Digest**, **Risks & Blockers**
- Chat: markdown render, token stream, cancel, copy
- Quick prompts: Overdue by assignee / Blocked tasks / Last week highlights
- **Cost widget:** this month's spend + trend from `agent_runs.cost_cents`

### Team / Consultant / Client SPA (web now, app later)

- **Today (web):** SPA served from **S3**
- **Later (app):** **Expo RN** targets iOS/Android using same API + Echo
- UI: **NativeWind** (Tailwind RN)
- Data: **TanStack Query** + Axios
- State: Zustand (optional)
- Validation: Zod
- Auth: Sanctum bearer to `/api/*`
- Realtime: laravel-echo + pusher-js/ably-js (or Reverb client when applicable)

**Participant agent UX**

- Project → **Agent** tab: chat + chips ("What changed today?", "My tasks this week", "Summarize last 20 messages")
- Streaming, retry/cancel, markdown

---

## Infrastructure (Render-only + S3 + Resend)

- Web server: **Nginx** proxy → **php-fpm**
- PHP extensions: intl, mbstring, bcmath, ctype, fileinfo, tokenizer, pdo_pgsql/pdo_mysql, redis, curl, openssl, xml, gd/imagick, zip
- Environment/config:

  - `.env` essentials: `APP_KEY`, `APP_URL`, DB*, REDIS*, MAIL*, QUEUE*, CACHE*DRIVER, FILESYSTEM_DISK, \*\*BROADCAST***\*, **AI*\*\*\*, `SANCTUM_STATEFUL_DOMAINS`, `CORS*\*`
  - Separate envs: local/staging/prod

- Storage/Uploads: **S3**; **signed URLs** for private objects (**no CloudFront**)
- Background workers: **Horizon** (Render worker service)
- WebSockets: **Reverb** on Render (or Pusher/Ably)
- Backups: automated DB + storage; retention policy
- Time: UTC everywhere; workers inherit

**Render hosting sketch**

- **Render Web Service:** Laravel API + Filament (`/api/*`, `/admin/*`)
- **Render Worker:** `php artisan horizon`
- **Render Managed Postgres:** primary DB
- **Render Managed Redis:** queues/cache/sessions/broadcast presence
- **Reverb on Render** (optional separate service) **or** Pusher/Ably
- **S3:** SPA web bundle + assets; private uploads with signed URLs
- **Resend:** transactional mail (invites, alerts, summaries)

---

## Developer Tooling

- Composer 2.x, PHP 8.3 CLI
- Static analysis: Larastan (PHPStan), Psalm (optional)
- Code style: Laravel Pint
- Tests: Pest or PHPUnit; Dusk if needed
- Fixtures: Factories + Seeders
- Git hooks: pre-commit (Pint, PHPStan, tests)
- VS Code extensions: Intelephense, Laravel Artisan, Blade formatter, Tailwind IntelliSense, EditorConfig

---

## Filament specifics (admins only)

- Resource pattern: Resource + Pages (List/Create/Edit) + form/table schemas
- Components: TextInput, Select (relations), FileUpload, DatePicker, etc.
- Policies: Laravel policies + spatie/permission
- Navigation: groups, icons, badges, global search

**Workspace scoping**

- `ProjectResource::getEloquentQuery()` filters by `auth()->user()->workspace_id`
- `UserResource` shows workspace selector only for admins
- RelationManager to attach team/consultant/client to projects (pivot role)

**Admin Agent page**

- Livewire + Echo streaming; server hits `/api/agent/*`

---

## Recommended packages (Composer)

- `filament/filament:^3`
- `livewire/livewire:^3`
- `laravel/sanctum`
- `spatie/laravel-permission`
- `spatie/laravel-activitylog`
- `spatie/laravel-backup`
- `laravel/horizon`
- `laravel/scout` (+ `meilisearch/meilisearch-php` or `algolia/scout-extended`) optional
- `intervention/image` **or** `spatie/laravel-medialibrary`
- `nunomaduro/larastan`
- `laravel/pint`
- `sentry/sentry-laravel` (or `bugsnag/bugsnag-laravel`)
- `laravel/telescope` (require-dev)
- **Broadcasting:** `pusher/pusher-php-server` (or `ably/ably-php`)
- **(Optional) Self-hosted websockets:** `beyondcode/laravel-websockets` (if not using Reverb)
- **AI:** `anthropic-php/anthropic-sdk-php` (default) **and** `openai-php/laravel` (fallback)

## Recommended packages (Node)

- (Admin) `tailwindcss`, `postcss`, `autoprefixer`, `alpinejs`, `@tailwindcss/forms`, `@tailwindcss/typography`, `vite`, `laravel-vite-plugin`, `react-markdown`, `remark-gfm`
- (SPA) `expo`, `react-native-web`, `nativewind`, `@tanstack/react-query`, `axios`, `zod`, `zustand`, `laravel-echo`, `pusher-js` (or `ably`), `react-markdown`, `remark-gfm`

---

## Install checklist (Linux)

```bash
# System
sudo apt-get update
sudo apt-get install -y nginx redis-server supervisor git unzip

# PHP 8.3 + extensions
sudo apt-get install -y php8.3 php8.3-fpm php8.3-cli php8.3-intl php8.3-mbstring php8.3-bcmath php8.3-xml php8.3-curl php8.3-gd php8.3-zip php8.3-redis php8.3-pgsql # or php8.3-mysql

# Node & Composer
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
npm i -g pnpm # optional
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# DB (dev/prod)
sudo apt-get install -y postgresql # or mysql-server

# Services up
sudo systemctl enable --now php8.3-fpm nginx redis-server
```

---

## CI/CD (two pipelines)

### Backend (GitHub Actions)

- Cache Composer/npm
- `composer install --no-dev --optimize-autoloader`
- `php artisan config:cache route:cache view:cache event:cache`
- `npm ci && npm run build` (Filament assets)
- Run tests (Pest/PHPUnit)
- Deploy to **Render** (build hook/container)
- Post-deploy: `php artisan migrate --force`; restart Horizon/queue; warm caches

### SPA (Expo)

- Lint, typecheck (if TS), unit tests
- Web: build RN Web → upload to **S3**
- iOS/Android (later): EAS builds
- Desktop (optional): Tauri artifacts

**Secrets:** `APP_KEY`, DB creds, REDIS, MAIL (Resend), FILESYSTEM_S3, **SANCTUM/CORS**, **PUSHER/ABLY or Reverb**, **AI_PROVIDER/AI_MODEL/KEYS**

---

## Configuration essentials

- Generate `APP_KEY`
- Set `APP_URL`; configure **CORS** and **SANCTUM_STATEFUL_DOMAINS** for SPA origins
- Session domain/cookie for admin; Sanctum tokens for SPA
- Queue/cache/session drivers = **Redis**
- Secure Horizon dashboard
- `php artisan storage:link`
- Mailer per env (Resend in prod)
- Scheduled backups + pruning Telescope/logs
- **Broadcasting** keys/host (Reverb on Render or Pusher/Ably)
- **AI**: provider/model keys; agent rate limits/budgets; PII redaction on
- **S3** buckets:

  - `rehome-spa-bundle` (public)
  - `rehome-private-uploads` (private with signed URLs)

- **AV scan** path chosen (ClamAV sidecar or S3→Lambda)

---

## Minimal API surface (SPA)

```
POST   /api/login
POST   /api/logout
GET    /api/me

GET    /api/projects
GET    /api/projects/{id}
GET    /api/projects/{id}/tasks
POST   /api/projects/{id}/tasks
GET    /api/projects/{id}/messages
POST   /api/projects/{id}/messages
```

### AI endpoints (shared by Admin & SPA)

```
POST   /api/projects/{project}/agent/threads
GET    /api/projects/{project}/agent/threads
GET    /api/agent/threads/{thread}
GET    /api/agent/threads/{thread}/messages
POST   /api/agent/threads/{thread}/messages
GET    /api/agent/threads/{thread}/stream   # optional if fully on Echo

GET    /api/projects/{project}/summaries
GET    /api/workspaces/{workspace}/summaries
```

---

## What changed vs. earlier drafts

1. **No CloudFront** — S3 only (signed URLs for private assets).
2. **Render-only** hosting pattern spelled out (Web + Worker + optional Reverb service).
3. **Default AI** set to **Claude 3.5 Haiku** for tone/price; OpenAI kept as targeted fallback.
4. Storage, mail, and realtime choices narrowed to **S3 + Resend + Reverb/Pusher/Ably**.
5. Emphasis on budgets/rate limits + graceful degradation for summaries.
