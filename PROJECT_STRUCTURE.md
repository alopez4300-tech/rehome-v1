# 🏗️ Rehome v1 - Project Structure

## 📁 **Root Level Organization**

```
rehome-v1/
├── 🔧 Configuration & Setup
├── 🚀 Applications (Backend & Frontend)  
├── 🐳 Infrastructure (Docker & DevContainer)
├── 📚 Documentation & Guides
├── 🤖 AI Assistant System
├── 🛠️ Development Tools & Scripts
└── 📋 Project Management Files
```

---

## 🚀 **Applications**

### 🔴 **Backend** (`/backend/`) - Laravel 11 + Filament 3
```
backend/
├── app/                     # Core application logic
│   ├── Console/            # Artisan commands
│   ├── Contracts/          # Interface definitions
│   ├── Events/            # Event classes
│   ├── Filament/          # Admin panel resources
│   ├── Http/              # Controllers, middleware
│   ├── Jobs/              # Queue job classes
│   ├── Models/            # Eloquent models
│   ├── Policies/          # Authorization policies
│   ├── Providers/         # Service providers
│   ├── Services/          # Business logic services
│   └── Traits/            # Reusable traits
├── config/                 # Configuration files
│   ├── ai.php             # AI system config
│   ├── horizon.php        # Queue dashboard
│   └── reverb.php         # WebSocket broadcasting
├── database/              # Database layer
│   ├── migrations/        # Schema migrations
│   ├── seeders/           # Data seeders
│   └── factories/         # Model factories
├── routes/                # Route definitions
│   ├── api.php           # API routes
│   ├── web.php           # Web routes
│   └── channels.php      # Broadcasting channels
└── public/               # Web-accessible files
    └── index.php         # Application entry point
```

### 🟢 **Frontend** (`/frontend/`) - React 18 + TypeScript + Vite
```
frontend/
├── src/                   # Source code
│   ├── assets/           # Static assets
│   ├── App.tsx          # Main app component
│   └── main.tsx         # App entry point
├── public/               # Public assets
├── package.json         # Node dependencies
└── vite.config.ts       # Build configuration
```

---

## 🐳 **Infrastructure**

### **Docker Configuration** (`/docker/`)
```
docker/
├── Dockerfile.php       # PHP-FPM container
├── nginx.conf          # Nginx web server config
└── php.ini            # PHP configuration
```

### **Development Container** (`/.devcontainer/`)
```
.devcontainer/
├── devcontainer.json          # VS Code dev container config
├── post-create.sh            # Setup script
├── create-backend-structure.sh   # Laravel setup
└── create-frontend-structure.sh  # React setup
```

### **Docker Compose Services**
- **app**: PHP-FPM (Laravel)
- **nginx**: Web server
- **postgres**: Database
- **redis**: Cache & queue
- **horizon**: Queue dashboard
- **frontend**: React dev server
- **mailpit**: Email testing
- **minio**: S3-compatible storage

---

## 🤖 **AI Assistant System** (`/ai/`)

```
ai/prompts/
├── 00_readme_first.md        # Quick start guide
├── 10_containers_healthcheck.md  # Health monitoring
├── 20_agent_layer_todos.md   # Implementation tasks
├── 30_render_s3_resend_envs.md   # Environment setup
└── 40_agent_system_status.md # System status guide
```

**Features:**
- AI Agent Chat System
- Daily/Weekly Digest Generation
- Risk Analysis & Monitoring
- Real-time WebSocket Streaming
- Cost Tracking & Optimization

---

## 📚 **Documentation** (`/docs/`)

```
docs/
├── agent_system_spec.md           # AI system specifications
├── development_guide.md          # Development workflow
├── project_assets_architecture.md # Asset management
├── project_assets_export_design.md # Export system
├── ENV.md                        # Environment variables
├── OPS.md                        # Operations guide
├── SETUP.md                      # Initial setup
└── stack_overview.md             # Technology stack
```

---

## 🛠️ **Development Tools**

### **Scripts** (`/scripts/`)
```
scripts/
├── dev/
│   ├── health-check.sh       # System health verification
│   ├── setup-containers.sh   # Container initialization
│   ├── validate-agents.sh    # AI system validation
│   └── validate-database.sh  # Database checks
└── dev-init.sh              # Quick development setup
```

### **Build & Quality Tools**
- **Makefile**: Development commands (`make help`)
- **PHPStan**: Static analysis
- **Laravel Pint**: Code formatting
- **Rector**: Code modernization
- **ESLint**: JavaScript linting
- **Prettier**: Code formatting

---

## ⚙️ **Configuration Files**

### **Root Level**
```
├── docker-compose.yml        # Multi-container orchestration
├── Makefile                 # Development commands
├── package.json            # Root-level Node dependencies
├── .editorconfig           # Editor settings
├── .gitignore             # Git ignore rules
└── .prettierrc.json       # Code formatting config
```

### **CI/CD** (`/.github/workflows/`)
```
.github/workflows/
├── ci.yml                  # Full CI pipeline
└── ci-light.yml           # Light development CI
```

---

## 📋 **Project Management**

```
├── README.md                      # Main project documentation
├── RUNBOOK.md                    # Operations runbook
├── PRODUCTION_COMPLETE.md        # Production readiness
├── PRODUCTION_POLISH_COMPLETE.md # Polish checklist
├── PRODUCTION_STREAMING_GUIDE.md # Streaming implementation
└── TEST_CLEANUP_SUMMARY.md      # Testing documentation
```

---

## 🌐 **Service URLs**

| Service | URL | Purpose |
|---------|-----|---------|
| **Admin Panel** | http://localhost:8000/admin | Filament admin interface |
| **API** | http://localhost:8000/api | REST API endpoints |
| **Horizon** | http://localhost:8000/horizon | Queue management |
| **Frontend** | http://localhost:3000 | React development server |
| **Storybook** | http://localhost:6006 | Component library |
| **Mailpit** | http://localhost:8025 | Email testing |
| **MinIO** | http://localhost:9001 | S3 storage console |

---

## 🔑 **Key Technologies**

- **Backend**: Laravel 11, Filament 3, PostgreSQL, Redis
- **Frontend**: React 18, TypeScript, Vite, TailwindCSS
- **AI**: OpenAI/Anthropic integration, WebSocket streaming
- **Infrastructure**: Docker, Nginx, Laravel Horizon
- **Development**: PHPStan, ESLint, Prettier, GitHub Actions

This structure provides a scalable, maintainable foundation for the Rehome platform with clear separation of concerns and comprehensive development tooling.