# ReHome v1 - AI Agent Management Platform

> **AI Assistant Quick Start**: This repo is **editor-agnostic** and ready for AI assistants (Cursor, VS AI, Windsurf). Run `make health-check` to validate your environment.

[![Development Status](https://img.shields.io/badge/status-active%20development-green)](https://github.com/alopez4300-tech/rehome-v1)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-red)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-v3-orange)](https://filamentphp.com)
[![React](https://img.shields.io/badge/React-18.x-blue)](https://reactjs.org)

## What is ReHome?

ReHome is an AI-powered project management platform that connects teams, consultants, and clients through intelligent agent assistants. Built with Laravel 11 and Filament v3, it provides real-time collaboration with AI agents that can summarize activities, answer questions, and provide insights scoped to user permissions.

### Key Features

- **🤖 AI Agent System** - Context-aware assistants with role-based access
- **👥 Multi-Role Access** - Admins, team members, consultants, and clients
- **📊 Real-time Updates** - WebSocket-powered live collaboration
- **🔒 Enterprise Security** - PII redaction, budget controls, rate limiting
- **📱 Multi-Platform** - Web admin panel + SPA for mobile/desktop

## 🚀 Quick Start (5 minutes)

### Prerequisites

- Docker & Docker Compose
- Git

### Setup

```bash
# 1. Clone and enter directory
git clone https://github.com/alopez4300-tech/rehome-v1.git
cd rehome-v1

# 2. Health check (validates environment)
make health-check

# 3. Complete setup if needed
make ai-setup

# 4. Verify everything works
make validate-db validate-agents
```

### Access Your Application

- **Frontend (React SPA)**: http://localhost:3000
- **Admin Panel (Filament)**: http://localhost:8000/admin
- **API Endpoints**: http://localhost:8000/api
- **Queue Dashboard (Horizon)**: http://localhost:8000/horizon

## 💡 Light Profile - Build & Run

ReHome starts in **Light Profile** by default - optimized for development and small teams with minimal dependencies.

### Light Profile Features

- **🗃️ SQLite Database** - No PostgreSQL required
- **📁 File-based Cache & Sessions** - No Redis required
- **⚡ Synchronous Queues** - No background workers needed
- **📧 Log-based Email** - No SMTP configuration needed
- **🔄 File-based Broadcasting** - No WebSocket server needed

### Quick Light Setup

```bash
# Use Light profile (default)
cp backend/.env.light.example backend/.env
php artisan key:generate
php artisan migrate --seed

# Start development server
php artisan serve &
cd frontend && npm run dev
```

### Switch to Scale Profile Later

When you're ready for production features (Redis, WebSockets, PostgreSQL):

```bash
make scale  # Switches to full-scale configuration
```

The **Light → Scale** transition is designed to be seamless - one command switches all drivers and enables advanced features.

### Essential Commands

```bash
# Development workflow
make up            # Start all containers
make down          # Stop containers
make logs          # View logs
make shell         # Enter app container

# Database operations
make migrate       # Run migrations
make seed          # Seed test data
make fresh         # Fresh DB with seed data

# Code quality
make lint          # Fix code formatting
make test          # Run test suite
make qa            # Run all quality checks

# AI agent system
make validate-agents    # Validate agent system
make queue-work         # Start background workers
make horizon           # Start queue dashboard
```

## 🏗️ Architecture Overview

```
┌─ Frontend (React + Vite) ──── Port 3000
├─ Backend (Laravel 11 + Filament) ──── Port 8000
├─ Database (PostgreSQL) ──── Port 5432
├─ Cache/Queue (Redis) ──── Port 6379
├─ WebSockets (Laravel Reverb) ──── Port 8080
└─ Queue Workers (Laravel Horizon) ──── Background
```

### Tech Stack

**Backend:** PHP 8.3, Laravel 11, Filament v3, PostgreSQL, Redis
**Frontend:** React 18, TypeScript, Vite, TailwindCSS
**AI System:** OpenAI/Anthropic integration, token streaming, cost tracking
**Infrastructure:** Docker, Laravel Horizon, Laravel Reverb, S3, Resend

## 📚 For Developers

### Documentation

- **[Stack Overview](/docs/stack_overview.md)** - Complete tech stack with Render + S3 + Resend
- **[Development Guide](/docs/development_guide.md)** - Complete setup and implementation guide
- **[Agent System Specification](/docs/agent_system_spec.md)** - AI agent architecture and usage
- **[Architecture Overview](/docs/architecture.md)** - System design and components
- **[Setup Guide](/docs/SETUP.md)** - 5-minute development setup
- **[Operations Manual](/docs/OPS.md)** - Troubleshooting and maintenance
- **[Environment Guide](/docs/ENV.md)** - Configuration reference

### AI Assistant Resources

- **[AI Prompts](/ai/prompts/)** - Ready-made context and instructions
- **[Health Check Scripts](/scripts/dev/)** - Validation and setup scripts

### Development Workflow

This repository is **editor-agnostic** and optimized for AI assistant collaboration:

```bash
# Instant environment validation
make health-check

# AI assistant onboarding
make ai-setup
make plan-agent-system

# Development commands
make help    # See all available commands
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Run quality checks (`make qa`)
4. Commit changes (`git commit -m 'feat: add amazing feature'`)
5. Push to branch (`git push origin feature/amazing-feature`)
6. Open a Pull Request

## 📄 License

This project is proprietary software. All rights reserved.

## 🆘 Support

- **Issues**: [GitHub Issues](https://github.com/alopez4300-tech/rehome-v1/issues)
- **Discussions**: [GitHub Discussions](https://github.com/alopez4300-tech/rehome-v1/discussions)
- **Documentation**: `/docs` directory
- **Health Check**: `make health-check`

---

**Ready to contribute?** Start with `make health-check` and see the [Development Guide](/docs/development_guide.md) for detailed instructions.
