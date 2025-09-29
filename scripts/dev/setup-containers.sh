#!/bin/bash

# Container Setup Script - Ensures all containers are healthy and ready
# This script provides deterministic container setup that AI assistants can use

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

print_header "Container Setup and Health Check"

# Check if Docker is available
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed or not in PATH"
    exit 1
fi

if ! docker info &> /dev/null; then
    print_error "Docker daemon is not running"
    exit 1
fi

print_success "Docker is available and running"

# Check docker-compose.yml exists
if [ ! -f "docker-compose.yml" ]; then
    print_error "docker-compose.yml not found"
    exit 1
fi

print_success "docker-compose.yml found"

# Build and start containers
print_header "Starting Containers"
print_info "Building and starting containers..."
docker-compose up -d --build

# Wait a bit for containers to fully start
print_info "Waiting for containers to fully initialize..."
sleep 10

# Check container health
print_header "Container Health Check"

# List of expected services
services=("app" "nginx" "postgres" "redis")

all_healthy=true
for service in "${services[@]}"; do
    if docker-compose ps "$service" | grep -q "Up"; then
        print_success "$service container is running"
    else
        print_error "$service container is not running"
        all_healthy=false
    fi
done

# Check specific service health
print_header "Service Health Check"

# Check PostgreSQL
print_info "Checking PostgreSQL connection..."
if docker-compose exec -T postgres pg_isready -U rehome_user -d rehome_db &> /dev/null; then
    print_success "PostgreSQL is accepting connections"
else
    print_warning "PostgreSQL connection check failed"
    all_healthy=false
fi

# Check Redis
print_info "Checking Redis connection..."
if docker-compose exec -T redis redis-cli ping | grep -q "PONG"; then
    print_success "Redis is responding"
else
    print_warning "Redis connection check failed"
    all_healthy=false
fi

# Check Laravel app
print_info "Checking Laravel application..."
if docker-compose exec -T app php artisan --version &> /dev/null; then
    print_success "Laravel application is responding"
else
    print_warning "Laravel application check failed"
    all_healthy=false
fi

# Check Nginx
print_info "Checking Nginx configuration..."
if docker-compose exec -T nginx nginx -t &> /dev/null; then
    print_success "Nginx configuration is valid"
else
    print_warning "Nginx configuration check failed"
    all_healthy=false
fi

# Show container logs for debugging if needed
if [ "$all_healthy" = false ]; then
    print_header "Container Logs (last 20 lines)"
    for service in "${services[@]}"; do
        echo -e "\n${YELLOW}--- $service logs ---${NC}"
        docker-compose logs --tail=20 "$service" 2>/dev/null || echo "No logs available for $service"
    done
fi

# Final status
print_header "Setup Complete"
if [ "$all_healthy" = true ]; then
    print_success "All containers are healthy and ready for development!"
    print_info "You can now access:"
    print_info "  - Frontend: http://localhost:3000"
    print_info "  - Backend: http://localhost:8000"
    print_info "  - Filament Admin: http://localhost:8000/admin"
    echo
    print_info "Useful commands:"
    print_info "  - make logs     # View container logs"
    print_info "  - make shell    # Enter app container"
    print_info "  - make migrate  # Run database migrations"
    print_info "  - make help     # View all available commands"
    exit 0
else
    print_error "Some containers may not be fully healthy"
    print_info "Check the logs above and try running 'make restart' if needed"
    exit 1
fi
