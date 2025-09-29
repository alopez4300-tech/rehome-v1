# 🤖 AI Assistant Quick Start

**Welcome to Rehome v1** - You're working with a Laravel 11 + Filament v3 monorepo with AI Agent functionality.

## 🚀 First Steps (Execute These)

1. **Check container health**: `make ai-status`
2. **Fix permissions**: `make perms`
3. **Start everything**: `make ai-setup`

## 📖 What You're Working With

- **Backend**: Laravel 11 + Filament v3 admin panel + API
- **AI Agent System**: Chat, Daily/Weekly Digests, Risk Analysis
- **Database**: PostgreSQL with test data (6 users, 1 workspace, 2 projects)
- **Queue**: Laravel Horizon for agent job processing
- **Broadcasting**: Laravel Reverb (WebSockets) for real-time streaming
- **Frontend**: Node.js/Vite dev server ready

## 🌐 Key URLs

- **Admin Panel**: `http://localhost:8000/admin` (login: `admin@test.com` / `password`)
- **Horizon**: `http://localhost:8000/horizon` (queue management)
- **API**: `http://localhost:8000/api/*` (REST endpoints)
- **Frontend**: `http://localhost:3000` (if needed)

## 📂 Critical Paths

- **Agent Models**: `backend/app/Models/Agent*.php`
- **Agent Controllers**: `backend/app/Http/Controllers/Api/Agent*.php`
- **Filament Agent Pages**: `backend/app/Filament/Resources/ProjectResource/Pages/AgentPage.php`
- **API Routes**: `backend/routes/api.php`
- **Migrations**: `backend/database/migrations/*agent*.php`

## 🔧 Common Commands

```bash
make help           # Show all available commands
make ai-status      # Quick status check
make logs          # Tail all service logs
make migrate       # Run database migrations
make cache-clear   # Clear Laravel caches
make test          # Run PHPUnit/Pest tests
make lint          # Run Laravel Pint formatting
make queue         # Start queue worker
```

## 🏥 If Something's Broken

1. **Permissions**: `make perms`
2. **Container issues**: `./scripts/dev/containers.sh`
3. **Environment check**: `./scripts/dev/check-env.sh`
4. **Fresh start**: `make down && make up`

## 📋 Next Tasks (Implementation Status)

✅ **Complete**:

- Agent models and migrations
- Filament admin interface with Agent tabs
- API routes and controllers
- Horizon configuration
- Broadcasting setup (Laravel Reverb)
- Cost tracking widget

🔨 **Ready to Implement**:

- AI provider integration (OpenAI/Anthropic)
- Real-time streaming via WebSockets
- Agent job classes for queue processing
- Digest generation logic
- Risk analysis algorithms

## 🎯 Quick Wins

1. Test the admin interface: Go to `/admin` → Projects → View → AI Agent tab
2. Check the API endpoints: `GET /api/projects` (needs Sanctum auth)
3. Monitor queues: Visit `/horizon` dashboard
4. Review agent models in `backend/app/Models/`

**Need more details?** Check `/ai/prompts/` for specific guidance on containers, agent implementation, and environment setup.
