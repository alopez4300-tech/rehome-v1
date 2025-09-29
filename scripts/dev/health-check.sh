#!/bin/bash

# Health Check Script - Validates development environment setup
# This script provides deterministic validation that AI assistants can use
# to verify the environment is ready for development work.

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
PASSED=0
FAILED=0
WARNINGS=0

# Helper functions
print_header() {
    echo -e "\n${BLUE}=== $1 ===${NC}"
}

print_pass() {
    echo -e "${GREEN}✓${NC} $1"
    PASSED=$((PASSED + 1))
}

print_fail() {
    echo -e "${RED}✗${NC} $1"
    FAILED=$((FAILED + 1))
}

print_warn() {
    echo -e "${YELLOW}⚠${NC} $1"
    WARNINGS=$((WARNINGS + 1))
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

# Change to project root
cd "$(dirname "$0")/../.."

print_header "ReHome Development Environment Health Check"

# Check Docker
print_header "Docker Environment"
if command -v docker &> /dev/null; then
    print_pass "Docker CLI available"

    if docker info &> /dev/null; then
        print_pass "Docker daemon running"
    else
        print_fail "Docker daemon not running or not accessible"
    fi

    if command -v docker-compose &> /dev/null; then
        print_pass "Docker Compose available"
    else
        print_fail "Docker Compose not available"
    fi
else
    print_fail "Docker not installed"
fi

# Check containers
print_header "Container Health"
if [ -f "docker-compose.yml" ]; then
    print_pass "docker-compose.yml exists"

    # Check if containers are running
    if docker-compose ps --services --filter "status=running" | grep -q .; then
        RUNNING_SERVICES=$(docker-compose ps --services --filter "status=running" | wc -l)
        print_pass "Containers running ($RUNNING_SERVICES services)"

        # Check specific services
        for service in app nginx postgres redis; do
            if docker-compose ps --services --filter "status=running" | grep -q "^${service}$"; then
                print_pass "$service container running"
            else
                print_warn "$service container not running"
            fi
        done
    else
        print_warn "No containers currently running"
        print_info "Run 'make up' to start the development environment"
    fi
else
    print_fail "docker-compose.yml not found"
fi

# Check Laravel backend
print_header "Laravel Backend"
if [ -d "backend" ]; then
    print_pass "Backend directory exists"

    if [ -f "backend/composer.json" ]; then
        print_pass "composer.json exists"
    else
        print_fail "composer.json missing"
    fi

    if [ -f "backend/artisan" ]; then
        print_pass "artisan command available"
    else
        print_fail "artisan command missing"
    fi

    if [ -f "backend/.env" ]; then
        print_pass ".env file exists"
    else
        print_warn ".env file missing - copy from .env.example"
    fi

    if [ -d "backend/vendor" ]; then
        print_pass "Composer dependencies installed"
    else
        print_warn "Composer dependencies not installed - run 'make install'"
    fi

    # Check database
    if [ -f "backend/database/database.sqlite" ]; then
        print_pass "SQLite database file exists"
    else
        print_warn "Database not set up - run 'make migrate'"
    fi
else
    print_fail "Backend directory not found"
fi

# Check Frontend (React + Vite)
print_header "Frontend (React + Vite)"
if [ -d "frontend" ]; then
    print_pass "Frontend directory exists"

    if [ -f "frontend/package.json" ]; then
        print_pass "package.json exists"
    else
        print_fail "package.json missing"
    fi

    if [ -f "frontend/vite.config.ts" ]; then
        print_pass "Vite configuration exists"
    else
        print_fail "vite.config.ts missing"
    fi

    if [ -d "frontend/node_modules" ]; then
        print_pass "NPM dependencies installed"
    else
        print_warn "NPM dependencies not installed - run 'make install'"
    fi
else
    print_fail "Frontend directory not found"
fi

# Check development tools
print_header "Development Tools"
if [ -f "Makefile" ]; then
    print_pass "Makefile exists"

    # Test a few key make targets
    if make -n help &> /dev/null; then
        print_pass "Make help target available"
    else
        print_warn "Make help target not working"
    fi
else
    print_fail "Makefile missing"
fi

if command -v git &> /dev/null; then
    print_pass "Git available"

    if [ -d ".git" ]; then
        print_pass "Git repository initialized"
    else
        print_warn "Not in a Git repository"
    fi
else
    print_fail "Git not installed"
fi

# Check AI assistant files
print_header "AI Assistant Files"
if [ -d "ai" ]; then
    print_pass "AI prompts directory exists"

    for file in "ai/prompts/00_readme_first.md" "ai/prompts/10_containers_healthcheck.md" "ai/prompts/20_agent_layer_todos.md" "ai/prompts/30_render_s3_resend_envs.md"; do
        if [ -f "$file" ]; then
            print_pass "$(basename "$file") available"
        else
            print_warn "$(basename "$file") missing"
        fi
    done
else
    print_warn "AI prompts directory missing"
fi

if [ -d "docs" ]; then
    print_pass "Documentation directory exists"

    for file in "docs/SETUP.md" "docs/OPS.md" "docs/ENV.md"; do
        if [ -f "$file" ]; then
            print_pass "$(basename "$file") available"
        else
            print_warn "$(basename "$file") missing"
        fi
    done
else
    print_warn "Documentation directory missing"
fi

# Check VS Code configuration
print_header "VS Code Configuration"
if [ -d ".vscode" ]; then
    print_pass ".vscode directory exists"

    for file in ".vscode/extensions.json" ".vscode/settings.json" ".vscode/tasks.json"; do
        if [ -f "$file" ]; then
            print_pass "$(basename "$file") configured"
        else
            print_warn "$(basename "$file") missing"
        fi
    done
else
    print_warn ".vscode directory missing"
fi

# Check hygiene configs
print_header "Code Quality Configuration"
hygiene_files=(".editorconfig" "backend/pint.json" "backend/phpstan.neon" ".prettierrc.json" ".gitmessage")
for file in "${hygiene_files[@]}"; do
    if [ -f "$file" ]; then
        print_pass "$(basename "$file") configured"
    else
        print_warn "$(basename "$file") missing"
    fi
done

# Final summary
print_header "Health Check Summary"
echo -e "Passed: ${GREEN}$PASSED${NC}"
echo -e "Failed: ${RED}$FAILED${NC}"
echo -e "Warnings: ${YELLOW}$WARNINGS${NC}"

if [ $FAILED -eq 0 ]; then
    if [ $WARNINGS -eq 0 ]; then
        echo -e "\n${GREEN}🎉 Environment is healthy and ready for development!${NC}"
        exit 0
    else
        echo -e "\n${YELLOW}⚠ Environment is functional but has some warnings.${NC}"
        echo -e "Consider addressing warnings for optimal development experience."
        exit 0
    fi
else
    echo -e "\n${RED}❌ Environment has critical issues that need to be resolved.${NC}"
    echo -e "Please fix failed checks before continuing development."
    exit 1
fi
