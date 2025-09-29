#!/bin/bash

# AI Agent System Validation Script
# Validates that the AI agent system components are working correctly

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_header() {
    echo -e "\n${BLUE}=== $1 ===${NC}"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Change to project root
cd "$(dirname "$0")/../.."

print_header "AI Agent System Validation"

# Check if Laravel is available
if ! docker-compose exec -T app php artisan --version &> /dev/null; then
    print_error "Laravel application is not accessible"
    exit 1
fi

print_success "Laravel application is accessible"

# Check AI Agent Models
print_header "AI Agent Models"

agent_models=("AgentThread" "AgentMessage" "AgentRun")
for model in "${agent_models[@]}"; do
    if docker-compose exec -T app php artisan tinker --execute="echo class_exists('App\\Models\\$model') ? 'exists' : 'missing';" 2>/dev/null | grep -q "exists"; then
        print_success "$model model exists"
    else
        print_error "$model model missing"
    fi
done

# Check database tables for AI agents
print_header "AI Agent Database Tables"
agent_tables=("agent_threads" "agent_messages" "agent_runs")

for table in "${agent_tables[@]}"; do
    if docker-compose exec -T app php artisan tinker --execute="echo DB::getSchemaBuilder()->hasTable('$table') ? 'exists' : 'missing';" 2>/dev/null | grep -q "exists"; then
        print_success "$table table exists"
    else
        print_error "$table table missing - run migrations"
    fi
done

# Check Filament configuration
print_header "Filament Admin Panel"

if [ -d "backend/app/Filament" ]; then
    print_success "Filament directory exists"

    if [ -d "backend/app/Filament/Resources" ]; then
        print_success "Filament Resources directory exists"

        # Check for AI agent resources
        agent_resources=$(find backend/app/Filament/Resources -name "*Agent*Resource.php" 2>/dev/null | wc -l)
        if [ "$agent_resources" -gt 0 ]; then
            print_success "AI Agent Filament resources found ($agent_resources resources)"
        else
            print_warning "No AI Agent Filament resources found"
        fi
    else
        print_warning "Filament Resources directory missing"
    fi
else
    print_error "Filament directory missing"
fi

# Check queue configuration for agents
print_header "Queue System for AI Agents"

if docker-compose exec -T app php artisan queue:work --help &> /dev/null; then
    print_success "Queue system is available"

    # Check for agent-specific queues in Horizon config
    if [ -f "backend/config/horizon.php" ]; then
        print_success "Laravel Horizon configuration exists"

        if grep -q "agent" backend/config/horizon.php; then
            print_success "Agent queues configured in Horizon"
        else
            print_warning "No agent-specific queues found in Horizon config"
        fi
    else
        print_warning "Laravel Horizon configuration missing"
    fi
else
    print_error "Queue system not available"
fi

# Check WebSocket configuration (Laravel Reverb)
print_header "WebSocket System (Laravel Reverb)"

if [ -f "backend/config/broadcasting.php" ]; then
    print_success "Broadcasting configuration exists"

    if grep -q "reverb" backend/config/broadcasting.php; then
        print_success "Laravel Reverb configured"
    else
        print_warning "Laravel Reverb not found in broadcasting config"
    fi
else
    print_warning "Broadcasting configuration missing"
fi

# Check environment variables for AI agents
print_header "AI Agent Environment Configuration"

if [ -f "backend/.env" ]; then
    env_vars=("OPENAI_API_KEY" "ANTHROPIC_API_KEY" "AGENT_MAX_CONCURRENT" "AGENT_TIMEOUT")

    for var in "${env_vars[@]}"; do
        if grep -q "^${var}=" backend/.env; then
            if grep "^${var}=" backend/.env | grep -q "=.*[^=]"; then
                print_success "$var is configured"
            else
                print_warning "$var is set but empty"
            fi
        else
            print_warning "$var not found in .env"
        fi
    done
else
    print_error ".env file missing"
fi

# Test basic model operations
print_header "Model Operation Tests"

print_info "Testing AgentThread model operations..."
if docker-compose exec -T app php artisan tinker --execute="
try {
    \$thread = new App\\Models\\AgentThread();
    \$thread->name = 'test-thread';
    \$thread->status = 'active';
    echo 'AgentThread model works';
} catch (Exception \$e) {
    echo 'AgentThread model error: ' . \$e->getMessage();
}
" 2>/dev/null | grep -q "works"; then
    print_success "AgentThread model operations work"
else
    print_warning "AgentThread model operations may have issues"
fi

# Check API routes for agents
print_header "API Routes"

if docker-compose exec -T app php artisan route:list 2>/dev/null | grep -q "agent"; then
    agent_routes=$(docker-compose exec -T app php artisan route:list 2>/dev/null | grep -c "agent" || echo "0")
    print_success "Agent API routes found ($agent_routes routes)"
else
    print_warning "No agent API routes found"
fi

# Check storage directories
print_header "Storage Configuration"

storage_dirs=("storage/app/agents" "storage/logs")
for dir in "${storage_dirs[@]}"; do
    if [ -d "backend/$dir" ]; then
        print_success "$dir directory exists"
    else
        print_warning "$dir directory missing"
    fi
done

print_header "AI Agent System Validation Complete"

# Summary
print_info "Summary:"
print_info "  - Ensure all AI agent models and tables exist"
print_info "  - Configure Filament resources for agent management"
print_info "  - Set up queue workers for background agent processing"
print_info "  - Configure WebSocket broadcasting for real-time updates"
print_info "  - Add API keys for AI services in .env file"

print_info "Next steps:"
print_info "  - make queue-work    # Start queue workers"
print_info "  - make horizon       # Start Horizon dashboard"
print_info "  - make reverb        # Start WebSocket server"
