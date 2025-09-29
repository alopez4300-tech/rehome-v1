#!/bin/bash

# Database Validation Script - Ensures database is properly set up and seeded
# This script provides deterministic database validation for AI assistants

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

print_header "Database Health Check and Validation"

# Check if Laravel is available in container
if ! docker-compose exec -T app php artisan --version &> /dev/null; then
    print_error "Laravel application is not accessible in container"
    print_info "Make sure containers are running with 'make up'"
    exit 1
fi

print_success "Laravel application is accessible"

# Check database configuration
print_header "Database Configuration"

# Check if .env exists
if [ ! -f "backend/.env" ]; then
    print_error "backend/.env file not found"
    print_info "Copy backend/.env.example to backend/.env and configure database settings"
    exit 1
fi

print_success ".env file exists"

# Check database connection
print_info "Testing database connection..."
if docker-compose exec -T app php artisan db:show &> /dev/null; then
    print_success "Database connection established"
else
    print_error "Cannot connect to database"
    print_info "Check database configuration in backend/.env"
    exit 1
fi

# Check migration status
print_header "Migration Status"
print_info "Checking migration status..."

# Run migrations if needed
migration_output=$(docker-compose exec -T app php artisan migrate:status 2>&1)
if echo "$migration_output" | grep -q "No migrations found"; then
    print_warning "No migrations found"
    print_info "Running fresh migrations..."
    docker-compose exec -T app php artisan migrate:fresh --seed
    print_success "Database migrated and seeded"
elif echo "$migration_output" | grep -q "Pending"; then
    print_warning "Pending migrations found"
    print_info "Running pending migrations..."
    docker-compose exec -T app php artisan migrate
    print_success "Migrations completed"
else
    print_success "All migrations are up to date"
fi

# Validate core tables exist
print_header "Table Validation"
expected_tables=("users" "workspaces" "projects" "agent_threads" "agent_messages" "agent_runs")

for table in "${expected_tables[@]}"; do
    if docker-compose exec -T app php artisan tinker --execute="echo DB::getSchemaBuilder()->hasTable('$table') ? 'exists' : 'missing';" 2>/dev/null | grep -q "exists"; then
        print_success "$table table exists"
    else
        print_warning "$table table missing or inaccessible"
    fi
done

# Check for sample data
print_header "Sample Data Check"
user_count=$(docker-compose exec -T app php artisan tinker --execute="echo App\\Models\\User::count();" 2>/dev/null | tail -1 | tr -d '\r')
if [ "${user_count:-0}" -gt 0 ]; then
    print_success "Sample users exist ($user_count users)"
else
    print_warning "No users found"
    print_info "Consider running 'make seed' to create sample data"
fi

workspace_count=$(docker-compose exec -T app php artisan tinker --execute="echo App\\Models\\Workspace::count();" 2>/dev/null | tail -1 | tr -d '\r')
if [ "${workspace_count:-0}" -gt 0 ]; then
    print_success "Sample workspaces exist ($workspace_count workspaces)"
else
    print_warning "No workspaces found"
    print_info "Sample data might need to be seeded"
fi

# Check queue configuration
print_header "Queue Configuration"
if docker-compose exec -T app php artisan queue:work --help &> /dev/null; then
    print_success "Queue system is available"

    # Check if Horizon is configured
    if docker-compose exec -T app php artisan horizon:status 2>/dev/null | grep -q "running\|inactive"; then
        print_success "Laravel Horizon is configured"
    else
        print_info "Laravel Horizon status unknown (this is normal if not started)"
    fi
else
    print_warning "Queue system not properly configured"
fi

# Check storage permissions
print_header "Storage Permissions"
if docker-compose exec -T app test -w storage/logs; then
    print_success "Storage directory is writable"
else
    print_warning "Storage directory permissions may be incorrect"
    print_info "Run 'make fix-permissions' if needed"
fi

# Check cache
print_header "Application Cache"
if docker-compose exec -T app php artisan config:cache &> /dev/null; then
    print_success "Configuration cache updated"
else
    print_warning "Configuration cache failed"
fi

if docker-compose exec -T app php artisan route:cache &> /dev/null; then
    print_success "Route cache updated"
else
    print_warning "Route cache failed"
fi

print_header "Database Validation Complete"
print_success "Database is ready for development!"
print_info "Useful database commands:"
print_info "  - make migrate      # Run pending migrations"
print_info "  - make seed         # Seed the database"
print_info "  - make fresh        # Fresh migration with seed"
print_info "  - make tinker       # Open Laravel Tinker REPL"
