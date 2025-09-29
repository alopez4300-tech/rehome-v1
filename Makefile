# --- Root Makefile (editor-agnostic) ---
# Any AI assistant (Cursor, VS AI, Windsurf) can use these commands
# Usage: make setup, make up, make migrate, etc.

APP?=app
WS?=/workspaces/rehome-v1
PHP=docker compose exec $(APP) php
ART=$(PHP) artisan
COMPOSER=docker compose exec $(APP) composer
NPM=npm --prefix backend

.DEFAULT_GOAL := help
.PHONY: help setup perms up down logs clean restart status

help: ## Show this help message
	@echo "🚀 Rehome v1 - Editor-Agnostic Commands"
	@echo "======================================"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# === Development Environment ===
setup: ## Install dependencies & build assets
	@echo "📦 Installing dependencies..."
	$(COMPOSER) install --no-dev --optimize-autoloader
	$(NPM) install
	$(NPM) run build
	@echo "✅ Setup complete!"

perms: ## Fix permissions for Codespaces -> other IDEs
	@echo "🔧 Fixing file permissions..."
	sudo chown -R vscode:vscode $(WS)
	@echo "✅ Permissions fixed!"

up: ## Start all containers
	@echo "🐳 Starting containers..."
	docker compose up -d
	@echo "✅ Containers started!"
	@echo "🚀 Services started!"
	@echo "Frontend: http://localhost:3000"
	@echo "Backend:  http://localhost:80"
	down: ## Stop all containers
	@echo "🛑 Stopping containers..."
	docker compose down
	@echo "✅ Containers stopped!"

restart: ## Restart all containers
	@echo "🔄 Restarting containers..."
	docker compose restart
	@echo "✅ Containers restarted!"

status: ## Show container status
	@echo "📊 Container Status:"
	@docker compose ps

logs: ## Tail logs for key services
	@echo "📜 Tailing logs (Ctrl+C to exit)..."
	docker compose logs -f app queue horizon nginx postgres redis

clean: ## Clean up containers and volumes
	@echo "🧹 Cleaning up..."
	docker compose down -v --remove-orphans
	docker system prune -f

# === Laravel Commands ===
migrate: ## Run database migrations
	@echo "🗄️  Running migrations..."
	$(ART) migrate --force
	@echo "✅ Migrations complete!"

seed: ## Seed database with test data
	@echo "🌱 Seeding database..."
	$(ART) db:seed --force
	@echo "✅ Database seeded!"

migrate-fresh: ## Fresh migration with seed
	@echo "🔄 Fresh migration with seed..."
	$(ART) migrate:fresh --seed --force
	@echo "✅ Fresh database ready!"

horizon: ## Start Horizon queue dashboard
	@echo "📊 Starting Horizon..."
	$(ART) horizon

cache: ## Optimize Laravel caches
	@echo "⚡ Optimizing caches..."
	$(ART) optimize
	$(ART) config:cache
	$(ART) route:cache
	$(ART) view:cache
	$(ART) event:cache
	@echo "✅ Caches optimized!"

cache-clear: ## Clear all Laravel caches
	@echo "🧹 Clearing caches..."
	$(ART) optimize:clear
	$(ART) config:clear
	$(ART) route:clear
	$(ART) view:clear
	$(ART) event:clear
	@echo "✅ Caches cleared!"

tinker: ## Open Laravel Tinker REPL
	$(ART) tinker

# === Queue & Jobs ===
queue: ## Start queue worker
	@echo "⚡ Starting queue worker..."
	docker compose exec queue php artisan queue:work --queue=default,agent --tries=3 --timeout=120

queue-restart: ## Restart queue workers
	@echo "🔄 Restarting queue workers..."
	$(ART) queue:restart

queue-failed: ## List failed jobs
	$(ART) queue:failed

queue-retry: ## Retry all failed jobs
	$(ART) queue:retry all

# === Tests & Quality Assurance ===
test: ## Run PHPUnit/Pest tests
	@echo "🧪 Running tests..."
	$(PHP) ./vendor/bin/pest -v

test-coverage: ## Run tests with coverage
	@echo "🧪 Running tests with coverage..."
	$(PHP) ./vendor/bin/pest --coverage

lint: ## Run Laravel Pint (code formatting)
	@echo "🎨 Running Laravel Pint..."
	$(PHP) ./vendor/bin/pint

lint-check: ## Check code formatting without fixing
	@echo "👀 Checking code formatting..."
	$(PHP) ./vendor/bin/pint --test

stan: ## Run PHPStan static analysis
	@echo "🔍 Running PHPStan..."
	$(PHP) ./vendor/bin/phpstan analyse

qa: lint stan test ## Run all quality assurance checks

# === Database Management ===
db-shell: ## Connect to database shell
	docker compose exec postgres psql -U postgres -d rehome

db-backup: ## Backup database
	@echo "💾 Creating database backup..."
	docker compose exec postgres pg_dump -U postgres rehome > backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "✅ Database backup created!"

db-restore: ## Restore database (usage: make db-restore FILE=backup.sql)
	@echo "📥 Restoring database from $(FILE)..."
	docker compose exec -T postgres psql -U postgres -d rehome < $(FILE)
	@echo "✅ Database restored!"

# === Frontend Commands ===
frontend-dev: ## Start frontend development server
	@echo "🎨 Starting frontend dev server..."
	docker compose up -d frontend

frontend-build: ## Build frontend for production
	@echo "🏗️  Building frontend..."
	$(NPM) run build

frontend-install: ## Install frontend dependencies
	@echo "📦 Installing frontend dependencies..."
	$(NPM) install

# === Debugging & Monitoring ===
shell: ## Open shell in app container
	docker compose exec $(APP) bash

logs-app: ## Show app container logs
	docker compose logs -f app

logs-nginx: ## Show nginx logs
	docker compose logs -f nginx

logs-horizon: ## Show horizon logs
	docker compose logs -f horizon

logs-queue: ## Show queue worker logs
	docker compose logs -f queue

# === Health Checks ===
health-check: ## Run comprehensive environment health check
	@./scripts/dev/health-check.sh

setup-containers: ## Build and validate all containers
	@./scripts/dev/setup-containers.sh

validate-db: ## Validate database setup and migrations
	@./scripts/dev/validate-database.sh

validate-agents: ## Validate AI agent system components
	@./scripts/dev/validate-agents.sh

health: health-check ## Alias for health-check (comprehensive environment validation)

# === Agent Development ===
plan-agent-system: ## Generate comprehensive agent implementation roadmap
	@echo "🎯 ReHome Agent System Implementation Plan"
	@echo "=========================================="
	@echo ""
	@echo "📋 PHASE 1: Core Agent Services (Priority 1)"
	@echo "  [ ] ContextBuilder.php - Token budget management (50/30/20 split)"
	@echo "  [ ] AgentService.php - Main orchestration service"
	@echo "  [ ] StreamingService.php - WebSocket token streaming"
	@echo "  [ ] CostTracker.php - Budget enforcement & rate limiting"
	@echo "  [ ] PIIRedactor.php - Sensitive data protection"
	@echo ""
	@echo "📋 PHASE 2: Event System & Broadcasting"
	@echo "  [ ] AgentMessageCreated event - Token streaming"
	@echo "  [ ] AgentSummaryReady event - Digest notifications"
	@echo "  [ ] Laravel Echo configuration"
	@echo "  [ ] WebSocket channel auth"
	@echo ""
	@echo "📋 PHASE 3: Automated Summaries"
	@echo "  [ ] DailyDigestJob - Per-project summaries"
	@echo "  [ ] WeeklyRollupJob - Workspace summaries"
	@echo "  [ ] Scheduler configuration"
	@echo "  [ ] Summary template system"
	@echo ""
	@echo "📋 PHASE 4: Filament Admin UI"
	@echo "  [ ] AgentChatPage.php - Chat interface"
	@echo "  [ ] AgentCostWidget.php - Budget tracking"
	@echo "  [ ] Quick prompt components"
	@echo "  [ ] Markdown rendering"
	@echo ""
	@echo "📋 PHASE 5: API Endpoints"
	@echo "  [ ] AgentController.php - Thread management"
	@echo "  [ ] Agent routes with Sanctum auth"
	@echo "  [ ] Project scoping middleware"
	@echo "  [ ] API rate limiting"
	@echo ""
	@echo "🚀 Quick Start: make scaffold-agent"

scaffold-agent: ## Create all agent service classes and structure
	@echo "🏗️  Scaffolding agent system structure..."
	@mkdir -p backend/app/Services/Agent
	@mkdir -p backend/app/Events/Agent
	@mkdir -p backend/app/Jobs/Agent
	@mkdir -p backend/app/Http/Controllers/Api
	@mkdir -p backend/storage/app/agents
	@echo "✅ Agent directories created"
	@echo "📁 Structure:"
	@echo "  backend/app/Services/Agent/ - Core agent services"
	@echo "  backend/app/Events/Agent/ - Broadcasting events"
	@echo "  backend/app/Jobs/Agent/ - Background processing"
	@echo "  backend/storage/app/agents/ - Agent data storage"

test-agent-streaming: ## Test WebSocket + token streaming
	@echo "🧪 Testing agent streaming..."
	@$(ART) test --filter=AgentStreaming

seed-agent-data: ## Create sample threads/messages for testing
	@echo "🌱 Seeding agent test data..."
	@$(ART) db:seed --class=AgentTestSeeder

# === Infrastructure Setup ===
setup-render: ## Interactive Render setup guide
	@echo "🚀 Render Deployment Setup"
	@echo "=========================="
	@echo "1. Create Render Web Service for Laravel backend"
	@echo "2. Create Render Worker for Laravel Horizon"
	@echo "3. Add Render Managed PostgreSQL"
	@echo "4. Add Render Managed Redis"
	@echo "5. Configure environment variables"
	@echo ""
	@echo "📖 See docs/RENDER.md for detailed steps"

setup-s3: ## S3 bucket + CloudFront configuration
	@echo "☁️  S3 + CloudFront Setup"
	@echo "========================"
	@echo "1. Create S3 buckets (public SPA, private uploads)"
	@echo "2. Configure bucket policies"
	@echo "3. Set up CloudFront distribution (optional)"
	@echo "4. Update .env with S3 credentials"
	@echo ""
	@echo "📖 See docs/S3.md for detailed steps"

setup-resend: ## Resend domain verification setup
	@echo "📧 Resend Email Setup"
	@echo "===================="
	@echo "1. Add domain to Resend dashboard"
	@echo "2. Configure DNS records (DKIM, SPF)"
	@echo "3. Verify domain"
	@echo "4. Update .env with Resend API key"
	@echo ""
	@echo "📖 See docs/RESEND.md for detailed steps"

# === Testing & Quality ===
test-full-agent: ## End-to-end agent test (context→LLM→stream)
	@echo "🧪 Running full agent system tests..."
	@$(ART) test --filter=AgentIntegration

benchmark-agent: ## Test agent performance and costs
	@echo "⚡ Benchmarking agent performance..."
	@$(ART) agent:benchmark

audit-agent-security: ## Verify PII redaction + scoping
	@echo "🔒 Auditing agent security..."
	@$(ART) agent:audit-security

# === Agent Commands ===
test-agent-config: ## Test agent system configuration
	@echo "🧪 Testing agent configuration..."
	@$(ART) agent:test --component=all

# === AI Assistant Helpers ===
ai-setup: perms up setup migrate seed ## Complete setup for AI assistants
	@echo "🤖 AI Assistant setup complete!"
	@echo "🌐 Admin Panel: http://localhost:8000/admin"
	@echo "📊 Horizon: http://localhost:8000/horizon"
	@echo "🔐 Login: admin@test.com / password"

ai-status: ## Quick status check for AI assistants
	@echo "🤖 AI Assistant Status Check"
	@echo "============================"
	@echo "📊 Containers:"
	@docker compose ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}"
	@echo ""
	@echo "🗄️  Database:"
	@$(ART) migrate:status | tail -5
	@echo ""
	@echo "🌐 URLs:"
	@echo "  Admin Panel: http://localhost:8000/admin"
	@echo "  Horizon:     http://localhost:8000/horizon"
	@echo "  API:         http://localhost:8000/api"
	@echo "  Frontend:    http://localhost:3000"

down: ## Stop all services
	docker-compose down -v
	@echo "🛑 Services stopped"

logs: ## Show logs for all services
	docker-compose logs -f

be: ## Start Laravel backend server (alternative to Docker)
	cd backend && php artisan serve --host=0.0.0.0 --port=8000

fe: ## Start React frontend dev server (alternative to Docker)
	cd frontend && npm run dev

storybook: ## Start Storybook
	cd frontend && npm run storybook

ci: ## Run all local quality checks
	@echo "🔍 Running backend checks..."
	cd backend && composer run-script lint
	cd backend && composer run-script typecheck
	cd backend && composer run-script test
	@echo "🔍 Running frontend checks..."
	cd frontend && npm run lint
	cd frontend && npm run typecheck
	cd frontend && npm run test:component
	cd frontend && npm run build
	cd frontend && npm run build:storybook
	@echo "✅ All checks passed!"

install: ## Install all dependencies
	cd backend && composer install
	cd frontend && npm install

setup: ## Initial project setup
	@echo "🏗️  Setting up Rehome project..."
	$(MAKE) install
	cd backend && cp .env.example .env
	cd backend && php artisan key:generate
	cd backend && php artisan migrate --force
	cd backend && php artisan db:seed --force
	@echo "✅ Setup complete!"

clean: ## Clean all caches and dependencies
	cd backend && php artisan cache:clear
	cd backend && php artisan config:clear
	cd backend && php artisan route:clear
	cd backend && php artisan view:clear
	cd frontend && rm -rf node_modules/.cache
	docker system prune -f

fresh: ## Fresh installation (clean + setup)
	$(MAKE) clean
	$(MAKE) down
	$(MAKE) setup
	$(MAKE) up
