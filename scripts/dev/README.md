# Development Scripts

This directory contains health check and validation scripts that AI assistants can use to verify the development environment is properly set up.

## Available Scripts

### `health-check.sh`

Comprehensive environment health check that validates:

- Docker environment and container status
- Laravel backend configuration
- React frontend setup
- Development tools availability
- AI assistant files and documentation
- VS Code configuration
- Code quality configurations

**Usage:**

```bash
./scripts/dev/health-check.sh
```

**Exit codes:**

- `0`: Environment is healthy
- `1`: Critical issues found

### `setup-containers.sh`

Builds and starts all Docker containers, then validates they are healthy and ready for development.

**Usage:**

```bash
./scripts/dev/setup-containers.sh
```

**What it does:**

- Builds and starts containers with `docker-compose up -d --build`
- Validates all services are running
- Tests database and Redis connectivity
- Checks Laravel and Nginx health
- Provides debugging information if issues are found

### `validate-database.sh`

Ensures the database is properly migrated, seeded, and ready for development.

**Usage:**

```bash
./scripts/dev/validate-database.sh
```

**Validations:**

- Database connection
- Migration status and execution
- Core table existence
- Sample data availability
- Queue system configuration
- Storage permissions
- Application cache

### `validate-agents.sh`

Validates the AI agent system components are properly configured and operational.

**Usage:**

```bash
./scripts/dev/validate-agents.sh
```

**AI Agent Checks:**

- Agent model classes (AgentThread, AgentMessage, AgentRun)
- Database tables for agent system
- Filament admin resources
- Queue configuration for background processing
- WebSocket system (Laravel Reverb)
- Environment variables for AI services
- Model operations testing
- API routes for agents

## Usage Patterns

### For AI Assistants

Run these scripts when:

1. **First time setup**: Start with `health-check.sh` to get environment overview
2. **Container issues**: Use `setup-containers.sh` to rebuild and validate containers
3. **Database problems**: Run `validate-database.sh` to check migrations and data
4. **Agent system debugging**: Use `validate-agents.sh` to verify AI components

### Quick Validation Flow

```bash
# Complete environment check
./scripts/dev/health-check.sh

# If containers need setup
./scripts/dev/setup-containers.sh

# If database issues found
./scripts/dev/validate-database.sh

# If working on AI agent features
./scripts/dev/validate-agents.sh
```

### Integration with Makefile

These scripts are also available through the Makefile:

```bash
make health-check      # Run health-check.sh
make setup-containers  # Run setup-containers.sh
make validate-db       # Run validate-database.sh
make validate-agents   # Run validate-agents.sh
```

## Output Format

All scripts use consistent color-coded output:

- 🟢 **Green ✓**: Successful checks
- 🟡 **Yellow ⚠**: Warnings (non-critical issues)
- 🔴 **Red ✗**: Errors (critical issues)
- 🔵 **Blue ℹ**: Informational messages

## Troubleshooting

If scripts report issues:

1. **Docker problems**: Ensure Docker daemon is running
2. **Container issues**: Try `make restart` or `make rebuild`
3. **Database issues**: Run `make migrate` or `make fresh`
4. **Permission issues**: Run `make fix-permissions`
5. **Missing files**: Check if you need to run `make install`

## Adding New Validations

When adding new features, extend the appropriate script:

- Add infrastructure checks to `health-check.sh`
- Add service-specific validations to dedicated scripts
- Update this README with new validation information
