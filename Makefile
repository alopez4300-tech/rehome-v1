.PHONY: help up down logs be fe ci clean

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

up: ## Start all services with Docker
	docker-compose up -d
	@echo "🚀 Services started!"
	@echo "Frontend: http://localhost:3000"
	@echo "Backend:  http://localhost:80"
	@echo "Admin:    http://localhost:80/admin"
	@echo "MailHog:  http://localhost:8025"

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