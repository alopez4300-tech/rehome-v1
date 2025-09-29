# Rehome v1 - Full-Stack Monorepo

A modern full-stack web application for rehoming pets, built with Laravel 11, Filament v3 admin panel, React 18, and comprehensive development tooling.

## 🏗️ Architecture

- **Backend**: Laravel 11 (PHP 8.3) with Filament v3 admin panel
- **Frontend**: React 18 + Vite + TypeScript + Tailwind CSS + shadcn/ui
- **Database**: MySQL 8.0
- **Authentication**: Laravel Sanctum + Spatie Permission
- **Testing**: PHPUnit, PHPStan, Vitest, Playwright, Storybook
- **CI/CD**: GitHub Actions with fast-gate and full-gate checks
- **Development**: Docker + devcontainer for local/Codespaces

## 🚀 Quick Start (GitHub Codespaces)

**The easiest way to get started - everything is automated!**

1. Click the green "Code" button on GitHub
2. Select "Codespaces" → "Create codespace on main"
3. Wait for the automated setup to complete (~5-8 minutes)
4. Everything will be ready to go!

The devcontainer will automatically:
- Install PHP 8.3, Node 20, Composer, MySQL client
- Set up Laravel backend with Filament admin panel
- Set up React frontend with Storybook
- Configure database and run migrations/seeders
- Install all dependencies
- Configure proper permissions

## 🛠️ Manual Setup (Local Development)

If you prefer to run locally with Docker:

```bash
# Clone the repository
git clone https://github.com/alopez4300-tech/rehome-v1.git
cd rehome-v1

# Start all services
make up

# Or setup everything from scratch
make fresh
```

## 📁 Project Structure

```
/
├─ .devcontainer/           # Codespace configuration
│  ├─ devcontainer.json     # Development container setup
│  └─ post-create.sh        # Automated setup script
├─ .github/workflows/       # CI/CD pipelines
│  └─ ci.yml               # Fast-gate & full-gate checks
├─ docker/                  # Docker configuration
│  ├─ nginx.conf           # Nginx web server config
│  ├─ php.ini              # PHP configuration
│  └─ Dockerfile.php       # PHP-FPM container
├─ backend/                 # Laravel 11 application
│  ├─ app/Models/          # Eloquent models (User, Workspace, Project)
│  ├─ app/Filament/        # Admin panel resources & pages
│  ├─ app/Policies/        # Authorization policies
│  ├─ database/migrations/ # Database schema
│  └─ database/seeders/    # Sample data
├─ frontend/                # React 18 application
│  ├─ src/components/      # React components + shadcn/ui
│  ├─ src/lib/            # Utilities & API client
│  ├─ .storybook/         # Storybook configuration
│  └─ playwright/         # E2E tests
├─ docker-compose.yml       # Multi-service orchestration
├─ Makefile                # Development shortcuts
└─ README.md               # This file
```

## 🔧 Available Commands

### Development Commands
```bash
make up          # Start all services with Docker
make down        # Stop all services
make logs        # Show logs for all services
make be          # Start Laravel backend server
make fe          # Start React frontend dev server
make storybook   # Start Storybook component library
```

### Quality Assurance Commands
```bash
make ci          # Run all quality checks locally
make setup       # Initial project setup
make clean       # Clean all caches
make fresh       # Fresh installation
```

### Backend-Specific Commands
```bash
cd backend
composer run-script lint      # Laravel Pint code formatting
composer run-script typecheck # PHPStan static analysis
composer run-script test      # PHPUnit test suite
composer run-script audit     # Security audit

php artisan migrate           # Run database migrations
php artisan db:seed          # Seed sample data
php artisan tinker           # Laravel REPL
```

### Frontend-Specific Commands
```bash
cd frontend
npm run dev           # Development server
npm run build         # Production build
npm run preview       # Preview production build
npm run lint          # ESLint
npm run typecheck     # TypeScript checking
npm run test          # Vitest unit tests
npm run test:e2e      # Playwright E2E tests
npm run storybook     # Storybook dev server
npm run build:storybook # Build Storybook static
npm run lhci          # Lighthouse CI
```

## 🌐 Service URLs

After starting the development environment:

| Service | URL | Description |
|---------|-----|-------------|
| Frontend | http://localhost:3000 | React dev server |
| Backend API | http://localhost:80 | Laravel application |
| Admin Panel | http://localhost:80/admin | Filament admin interface |
| Storybook | http://localhost:6006 | Component library |
| MailHog | http://localhost:8025 | Email testing |
| Database | localhost:3306 | MySQL (user: app, pass: app) |

## 👤 Admin Access

After the automated setup completes, you can access the admin panel with these seeded accounts:

- **Admin 1**: admin1@rehome.build / password (Workspace: "Acme Construction")
- **Admin 2**: admin2@rehome.build / password (Workspace: "Beta Corp")

Each admin only sees projects from their assigned workspace.

## 🔄 CI/CD Pipeline

The project includes comprehensive CI/CD with two gate levels:

### Fast Gate (Every Push)
- Backend: Lint (Pint), Typecheck (PHPStan), Unit tests (PHPUnit)
- Frontend: Lint (ESLint), Typecheck (TypeScript), Build (Vite)

### Full Gate (Pull Requests Only)
- All fast-gate checks
- Backend: Full test suite, Security audit
- Frontend: Component tests (Vitest), E2E tests (Playwright)
- Storybook build verification
- Lighthouse CI performance checks

## 🏢 Workspace-Based Architecture

The application uses a multi-tenant workspace system:

- **Workspaces**: Isolated environments for different organizations
- **Users**: Assigned to workspaces with roles (admin, team, consultant, client)
- **Projects**: Scoped to workspaces, with user assignments and roles
- **Admin Panel**: Admins only see data from their assigned workspace

## 📚 Key Features

### Backend (Laravel + Filament)
- ✅ User management with role-based permissions
- ✅ Workspace-scoped project management
- ✅ Soft deletes with restore functionality
- ✅ User-project assignments with pivot roles
- ✅ Inline workspace creation in user forms
- ✅ Tabbed project views (Active, On Hold, Completed, Archived, Trash)
- ✅ Comprehensive policies for access control

### Frontend (React + Vite)
- ✅ VS Code-inspired dashboard layout
- ✅ Resizable sidebar navigation
- ✅ Component library with Storybook
- ✅ Tailwind CSS + shadcn/ui components
- ✅ TypeScript for type safety
- ✅ API client with React Query

### Development Experience
- ✅ One-click Codespace setup
- ✅ Hot reloading for frontend and backend
- ✅ Comprehensive test coverage
- ✅ Automated code formatting and linting
- ✅ Database migrations and seeding
- ✅ Email testing with MailHog

## 🐛 Troubleshooting

### Codespace Issues
If the automated setup fails, try:
```bash
# Re-run the setup script
bash .devcontainer/post-create.sh

# Or manually set up
make fresh
```

### Database Issues
```bash
# Reset database
cd backend
php artisan migrate:fresh --seed
```

### Permission Issues
```bash
# Fix Laravel permissions
cd backend
chmod -R 775 storage bootstrap/cache
```

### Frontend Issues
```bash
# Clear npm cache and reinstall
cd frontend
rm -rf node_modules package-lock.json
npm install
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes
4. Run quality checks: `make ci`
5. Commit your changes: `git commit -m 'Add amazing feature'`
6. Push to the branch: `git push origin feature/amazing-feature`
7. Open a Pull Request

## 📄 License

This project is licensed under the MIT License.

---

**Ready to start? Just open this repo in GitHub Codespaces and everything will be set up automatically! 🚀**