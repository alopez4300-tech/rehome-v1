# ReHome v1# ReHome v1 - AI Agent Management Platform

> Modern Real Estate Project Management Platform> **AI Assistant Quick Start**: This repo is **editor-agnostic** and ready for AI assistants (Cursor, VS AI, Windsurf). Run `make health-check` to validate your environment.

A comprehensive platform designed to streamline workflows between admins, team members, consultants, and clients. Built with Laravel 11, modern frontend SPA, and intelligent AI chat assistants.[![Development Status](https://img.shields.io/badge/status-active%20development-green)](https://github.com/alopez4300-tech/rehome-v1)

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red)](https://laravel.com)

## Quick Start[![Filament](https://img.shields.io/badge/Filament-v3-orange)](https://filamentphp.com)

[![React](https://img.shields.io/badge/React-18.x-blue)](https://reactjs.org)

````bash

# Clone and setup## What is ReHome?

git clone <repository-url> rehome-v1

cd rehome-v1ReHome is an AI-powered project management platform that connects teams, consultants, and clients through intelligent agent assistants. Built with Laravel 11 and Filament v3, it provides real-time collaboration with AI agents that can summarize activities, answer questions, and provide insights scoped to user permissions.

make setup

### Key Features

# Start development

make up && make dev- **🤖 AI Agent System** - Context-aware assistants with role-based access

- **👥 Multi-Role Access** - Admins, team members, consultants, and clients

# Verify installation- **📊 Real-time Updates** - WebSocket-powered live collaboration

make health-check- **🔒 Enterprise Security** - PII redaction, budget controls, rate limiting

```- **📱 Multi-Platform** - Web admin panel + SPA for mobile/desktop



**Access Points:**## 🚀 Quick Start (5 minutes)

- **Frontend**: http://localhost:5173

- **Admin Panel**: http://localhost/admin  ### Prerequisites

- **API Documentation**: http://localhost/docs/api- Docker & Docker Compose

- **Queue Dashboard**: http://localhost/horizon- Git



## ✨ Key Features### Setup



### 🏢 **Multi-Tenant Workspaces**```bash

Complete workspace isolation with role-based access control for admins, team members, consultants, and clients.# 1. Clone and enter directory

git clone https://github.com/alopez4300-tech/rehome-v1.git

### 📋 **Project & Task Management** cd rehome-v1

Full lifecycle project management with task tracking, file attachments, progress monitoring, and milestone management.

# 2. Health check (validates environment)

### 🤖 **AI Chat Assistants**make health-check

Context-aware agents with real-time streaming, cost controls, privacy protection, and role-based data access.

# 3. Complete setup if needed

### 📊 **Analytics & Reporting**make ai-setup

Project dashboards, team performance analytics, budget tracking, and automated report generation.

# 4. Verify everything works

### 🔐 **Enterprise Security**make validate-db validate-agents

Multi-factor authentication, audit logging, role-based permissions, and API security controls.```



## 🏗️ Architecture### Access Your Application



```mermaid- **Frontend (React SPA)**: http://localhost:3000

graph TB- **Admin Panel (Filament)**: http://localhost:8000/admin

    subgraph "Client Layer"- **API Endpoints**: http://localhost:8000/api

        SPA[Frontend SPA]- **Queue Dashboard (Horizon)**: http://localhost:8000/horizon

        ADMIN[Admin Panel]

    end### Essential Commands



    subgraph "API Layer"```bash

        REST[REST API]# Development workflow

        WS[WebSockets]make up            # Start all containers

    endmake down          # Stop containers

    make logs          # View logs

    subgraph "Application"make shell         # Enter app container

        AUTH[Authentication]

        AGENTS[AI Agents]# Database operations

        PROJECTS[Projects]make migrate       # Run migrations

        TASKS[Tasks]make seed          # Seed test data

    endmake fresh         # Fresh DB with seed data



    subgraph "Data"# Code quality

        DB[(PostgreSQL)]make lint          # Fix code formatting

        REDIS[(Redis)]make test          # Run test suite

    endmake qa            # Run all quality checks



    subgraph "External"# AI agent system

        AI[AI Providers]make validate-agents    # Validate agent system

    endmake queue-work         # Start background workers

    make horizon           # Start queue dashboard

    SPA --> REST```

    ADMIN --> REST

    SPA --> WS## 🏗️ Architecture Overview



    REST --> AUTH```

    AUTH --> AGENTS┌─ Frontend (React + Vite) ──── Port 3000

    AUTH --> PROJECTS├─ Backend (Laravel 11 + Filament) ──── Port 8000

    AUTH --> TASKS├─ Database (PostgreSQL) ──── Port 5432

    ├─ Cache/Queue (Redis) ──── Port 6379

    AGENTS --> DB├─ WebSockets (Laravel Reverb) ──── Port 8080

    PROJECTS --> DB└─ Queue Workers (Laravel Horizon) ──── Background

    TASKS --> DB```



    AGENTS --> REDIS### Tech Stack

    AGENTS --> AI

```**Backend:** PHP 8.3, Laravel 11, Filament v3, PostgreSQL, Redis

**Frontend:** React 18, TypeScript, Vite, TailwindCSS

## 🚀 Tech Stack**AI System:** OpenAI/Anthropic integration, token streaming, cost tracking

**Infrastructure:** Docker, Laravel Horizon, Laravel Reverb, S3, Resend

**Backend**: Laravel 11 + Filament v3

**Frontend**: Modern SPA (React/Vue) + TypeScript  ## 📚 For Developers

**Database**: PostgreSQL + Redis

**WebSockets**: Laravel Reverb  ### Documentation

**AI**: OpenAI/Anthropic with streaming

**Infrastructure**: Docker + Nginx  - **[Development Guide](/docs/development_guide.md)** - Complete setup and implementation guide

- **[Agent System Specification](/docs/agent_system_spec.md)** - AI agent architecture and usage

## 👥 User Roles- **[Architecture Overview](/docs/architecture.md)** - System design and components

- **[Setup Guide](/docs/SETUP.md)** - 5-minute development setup

| Role | Access | AI Permissions | Use Case |- **[Operations Manual](/docs/OPS.md)** - Troubleshooting and maintenance

|------|--------|----------------|----------|- **[Environment Guide](/docs/ENV.md)** - Configuration reference

| **Admin** | Workspace-wide | Full context, financial data | Workspace owners |

| **Team** | Assigned projects | Project context, team comms | Project managers |### AI Assistant Resources

| **Consultant** | Contracted work | Task-specific, filtered data | External contractors |

| **Client** | Their projects | High-level summaries, safe content | Property owners |- **[AI Prompts](/ai/prompts/)** - Ready-made context and instructions

- **[Health Check Scripts](/scripts/dev/)** - Validation and setup scripts

## 🛠️ Development

### Development Workflow

### Common Commands

This repository is **editor-agnostic** and optimized for AI assistant collaboration:

```bash

# Environment Management```bash

make up             # Start containers# Instant environment validation

make down           # Stop containers  make health-check

make dev            # Development mode

make shell          # Access app shell# AI assistant onboarding

make ai-setup

# Database Operationsmake plan-agent-system

make migrate        # Run migrations

make seed           # Seed test data# Development commands

make fresh          # Fresh DB with seedmake help    # See all available commands

````

# Testing & Quality

make test # Full test suite## 🤝 Contributing

make lint # Code style check

make health-check # System validation1. Fork the repository

2. Create a feature branch (`git checkout -b feature/amazing-feature`)

# AI System3. Run quality checks (`make qa`)

make validate-agents # Test AI components4. Commit changes (`git commit -m 'feat: add amazing feature'`)

make test-agent-config # Validate AI config5. Push to branch (`git push origin feature/amazing-feature`)

````6. Open a Pull Request



### Prerequisites## 📄 License



- Docker & Docker Compose 20.10+This project is proprietary software. All rights reserved.

- Make (command runner)

- Git## 🆘 Support



### Setup Process- **Issues**: [GitHub Issues](https://github.com/alopez4300-tech/rehome-v1/issues)

- **Discussions**: [GitHub Discussions](https://github.com/alopez4300-tech/rehome-v1/discussions)

1. **Clone & Configure**:- **Documentation**: `/docs` directory

   ```bash- **Health Check**: `make health-check`

   git clone <repo> rehome-v1

   cd rehome-v1---

   cp .env.example .env

   # Edit .env with your API keys**Ready to contribute?** Start with `make health-check` and see the [Development Guide](/docs/development_guide.md) for detailed instructions.
````

2. **Install & Start**:

   ```bash
   make setup    # One-command installation
   make dev      # Start development
   ```

3. **Verify**:
   ```bash
   make health-check  # Validates 40+ system components
   ```

### Default Access

**Admin Login**: admin@rehome.local / password
**Frontend**: http://localhost:5173
**Admin Panel**: http://localhost/admin

## 📡 API Overview

### Authentication

```http
Authorization: Bearer your-sanctum-token
```

### Core Endpoints

```
GET    /api/projects                    # List user projects
POST   /api/projects/{id}/tasks         # Create task
GET    /api/projects/{id}/agent/threads # AI conversations
POST   /api/projects/{id}/files         # Upload files
```

### WebSocket Events

```javascript
// Real-time updates
Echo.private('project.123')
    .listen('TaskUpdated', (e) => { ... })
    .listen('AgentMessageCreated', (e) => { ... });
```

## ⚙️ Configuration

### Environment Setup

```bash
# Core
APP_URL=http://localhost
DB_CONNECTION=pgsql

# AI Configuration
AI_PROVIDER=openai
OPENAI_API_KEY=your-key
AI_MODEL=gpt-4o-mini

# WebSocket/Queue
BROADCAST_DRIVER=reverb
QUEUE_CONNECTION=redis
```

### AI System Config

Edit `config/ai.php` for:

- Token budgets & cost limits
- PII redaction patterns
- Rate limiting policies
- Provider fallback rules

## 🚀 Deployment

### Staging

```bash
make deploy-staging
make health-check
```

### Production

```bash
# Configure production environment
cp .env.production .env

# Deploy with zero downtime
make deploy-prod
make monitor
```

### Infrastructure Requirements

- **CPU**: 2+ cores
- **RAM**: 4GB+
- **Storage**: 50GB+ SSD
- **PHP**: 8.3+ with extensions
- **Database**: PostgreSQL 15+
- **Cache/Queue**: Redis 6+

## 📚 Documentation

### Developer Resources

- **[Development Guide](docs/development_guide.md)** - Complete setup and architecture guide
- **[AI Agent System](docs/agent_system_spec.md)** - AI system specification and API
- **[Architecture Overview](docs/architecture.md)** - System design and patterns
- **[Deployment Guide](docs/deployment.md)** - Production deployment procedures

### API Documentation

- **OpenAPI Spec**: Available at `/docs/api` when running
- **WebSocket Events**: Real-time event specifications
- **Authentication**: Sanctum token management

## 🤝 Contributing

1. **Fork & Branch**: Create feature branches from `main`
2. **Code Standards**: Follow PSR-12 (PHP) and ESLint (JS/TS)
3. **Testing**: Write tests for new features (`make test`)
4. **Documentation**: Update relevant docs and API specs
5. **Submit PR**: Include tests and clear description

### Code Quality

```bash
make lint        # Check code style
make fix         # Auto-fix issues
make test        # Run full test suite
make coverage    # Generate coverage report
```

## 📄 License

Proprietary software. All rights reserved.

---

**ReHome v1** - Streamlined real estate project management with intelligent automation
