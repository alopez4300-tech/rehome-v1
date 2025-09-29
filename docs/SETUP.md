# 🚀 Quick Setup Guide

Get Rehome v1 running in any IDE (Cursor, VS AI, Windsurf, etc.) in under 5 minutes.

## Prerequisites

- Docker and Docker Compose
- Node.js 20+ (for frontend development)
- Make (available on most systems)

## 1. Quick Start

```bash
# Clone and enter directory
git clone <your-repo-url>
cd rehome-v1

# One-command setup for AI assistants
make ai-setup
```

This will:

- Fix file permissions
- Start all containers
- Install dependencies
- Run migrations
- Seed test data

## 2. Access the Application

| Service         | URL                           | Credentials                   |
| --------------- | ----------------------------- | ----------------------------- |
| **Admin Panel** | http://localhost:8000/admin   | `admin@test.com` / `password` |
| **Horizon**     | http://localhost:8000/horizon | (no auth required)            |
| **API**         | http://localhost:8000/api     | (Sanctum tokens required)     |
| **Frontend**    | http://localhost:3000         | (development server)          |

## 3. Available Commands

```bash
make help           # Show all commands
make ai-status      # Quick health check
make logs          # View all service logs
make shell         # Open shell in app container
make test          # Run tests
make lint          # Format code
```

## 4. Development Workflow

### Backend (Laravel)

```bash
make migrate        # Run database migrations
make seed          # Seed test data
make tinker        # Laravel REPL
make cache-clear   # Clear caches
```

### Frontend

```bash
make frontend-dev   # Start dev server
make frontend-build # Build for production
```

### Queue System

```bash
make queue         # Start queue worker
make horizon       # Open Horizon dashboard
```

## 5. Project Structure

```
rehome-v1/
├── backend/              # Laravel 11 application
│   ├── app/Models/       # Agent*, Project, User, Workspace
│   ├── routes/api.php    # REST API endpoints
│   └── app/Filament/     # Admin interface
├── frontend/             # Node.js/Vite frontend
├── docker/               # Docker configuration
├── ai/prompts/           # AI assistant guides
├── docs/                 # Documentation
└── Makefile             # Standardized commands
```

## 6. What's Pre-configured

✅ **Database**: PostgreSQL with test data
✅ **Authentication**: Sanctum for API, sessions for admin
✅ **Queue System**: Redis + Horizon
✅ **Broadcasting**: Laravel Reverb (WebSockets)
✅ **AI Agent System**: Models, controllers, UI ready
✅ **File Storage**: Local (dev), S3-ready (prod)
✅ **Email**: Mailpit (dev), Resend-ready (prod)

## 7. Next Steps

1. **Test the admin interface**: Visit `/admin` and explore the AI Agent tabs
2. **Check API endpoints**: Use Postman or curl to test `/api/projects`
3. **Add AI provider**: Set OpenAI/Anthropic keys in `.env`
4. **Implement agent jobs**: Create AI processing logic
5. **Deploy**: See `docs/OPS.md` for production deployment

## 🆘 Troubleshooting

### Containers won't start

```bash
make perms          # Fix permissions
make down && make up # Restart everything
```

### Permission denied errors

```bash
sudo chown -R vscode:vscode /workspaces/rehome-v1
```

### Database connection issues

```bash
make logs-postgres  # Check PostgreSQL logs
make migrate       # Ensure migrations ran
```

### Need help?

- Check `ai/prompts/10_containers_healthcheck.md`
- Run `./scripts/dev/containers.sh` for detailed status
- Use `make ai-status` for quick overview

Ready to build! 🚀
