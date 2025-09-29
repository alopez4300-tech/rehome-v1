#!/bin/bash
set -e

echo "🚀 Initializing Rehome Laravel + Filament Development Environment"
echo "================================================================="

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if we're in the right directory
if [ ! -f "docker-compose.yml" ]; then
    print_error "docker-compose.yml not found. Please run this script from the project root."
    exit 1
fi

# Start services
print_status "Starting Docker services..."
docker-compose up -d postgres redis mailpit minio

# Wait for PostgreSQL to be ready
print_status "Waiting for PostgreSQL to be ready..."
timeout=30
counter=0
until docker-compose exec postgres pg_isready -U rehome -d rehome >/dev/null 2>&1; do
    if [ $counter -eq $timeout ]; then
        print_error "PostgreSQL failed to start within $timeout seconds"
        exit 1
    fi
    printf "."
    sleep 1
    counter=$((counter + 1))
done
echo ""

# Check if Laravel backend exists
if [ ! -d "backend" ] || [ ! -f "backend/composer.json" ]; then
    print_status "Creating fresh Laravel 11 project..."
    rm -rf backend
    docker-compose run --rm app composer create-project laravel/laravel . --no-interaction
    
    # Set proper permissions
    docker-compose exec app chown -R app:app /var/www/html
fi

# Install PHP dependencies
print_status "Installing PHP dependencies..."
docker-compose run --rm app composer install --no-interaction

# Install our core packages
print_status "Installing Filament, Sanctum, and core packages..."
docker-compose run --rm app composer require \
    filament/filament:^3 \
    livewire/livewire:^3 \
    laravel/sanctum \
    spatie/laravel-permission \
    sentry/sentry-laravel \
    --with-all-dependencies --no-interaction

# Install dev dependencies
print_status "Installing development dependencies..."
docker-compose run --rm app composer require --dev \
    laravel/telescope \
    nunomaduro/larastan \
    pestphp/pest \
    pestphp/pest-plugin-laravel \
    laravel/pint \
    --no-interaction

# Copy environment file
if [ ! -f "backend/.env" ]; then
    print_status "Creating .env file..."
    docker-compose run --rm app cp .env.example .env
fi

# Generate application key
print_status "Generating application key..."
docker-compose run --rm app php artisan key:generate --no-interaction

# Configure database in .env
print_status "Configuring database connection..."
docker-compose exec app sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=pgsql/' .env
docker-compose exec app sed -i 's/DB_HOST=127.0.0.1/DB_HOST=postgres/' .env
docker-compose exec app sed -i 's/DB_PORT=3306/DB_PORT=5432/' .env
docker-compose exec app sed -i 's/DB_DATABASE=laravel/DB_DATABASE=rehome/' .env
docker-compose exec app sed -i 's/DB_USERNAME=root/DB_USERNAME=rehome/' .env
docker-compose exec app sed -i 's/DB_PASSWORD=/DB_PASSWORD=secret/' .env

# Configure Redis
print_status "Configuring Redis..."
docker-compose exec app sed -i 's/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/' .env
docker-compose exec app sed -i 's/CACHE_DRIVER=file/CACHE_DRIVER=redis/' .env
docker-compose exec app sed -i 's/QUEUE_CONNECTION=sync/QUEUE_CONNECTION=redis/' .env
docker-compose exec app sed -i 's/SESSION_DRIVER=file/SESSION_DRIVER=redis/' .env

# Configure mail
print_status "Configuring mail settings..."
docker-compose exec app sed -i 's/MAIL_HOST=smtp.mailgun.org/MAIL_HOST=mailpit/' .env
docker-compose exec app sed -i 's/MAIL_PORT=587/MAIL_PORT=1025/' .env

# Install Sanctum
print_status "Installing Laravel Sanctum..."
docker-compose run --rm app php artisan sanctum:install --no-interaction

# Publish spatie/permission
print_status "Publishing spatie/permission configuration..."
docker-compose run --rm app php artisan vendor:publish --provider="Spatie\\Permission\\PermissionServiceProvider"

# Install Telescope (dev only)
print_status "Installing Laravel Telescope..."
docker-compose run --rm app php artisan telescope:install --no-interaction

# Run migrations
print_status "Running database migrations..."
docker-compose run --rm app php artisan migrate --no-interaction

# Create storage link
print_status "Creating storage link..."
docker-compose run --rm app php artisan storage:link

# Check if frontend exists
if [ ! -d "frontend" ] || [ ! -f "frontend/package.json" ]; then
    print_status "Creating React TypeScript frontend..."
    rm -rf frontend
    mkdir frontend
    docker-compose run --rm frontend sh -c "cd /app && npm create vite@latest . -- --template react-ts --yes"
fi

# Install frontend dependencies
print_status "Installing frontend dependencies..."
docker-compose run --rm frontend npm install

# Install additional frontend packages
print_status "Installing Tailwind CSS and Alpine.js..."
docker-compose run --rm frontend npm install -D \
    tailwindcss \
    postcss \
    autoprefixer \
    @tailwindcss/forms \
    @tailwindcss/typography

docker-compose run --rm frontend npm install alpinejs

# Start all services
print_status "Starting all services..."
docker-compose up -d

print_status "Development environment initialized successfully!"
echo ""
echo "🌟 Your Rehome application is ready!"
echo "=================================="
echo "🔗 Laravel Backend (Nginx): http://localhost:8000"
echo "🔗 React Frontend (Vite):   http://localhost:3000"
echo "📧 Mailpit (Email testing): http://localhost:8025"
echo "💾 MinIO Console (S3):      http://localhost:9001"
echo "🗄️  PostgreSQL:             localhost:5432"
echo "🚀 Redis:                   localhost:6379"
echo ""
echo "📝 Next steps:"
echo "   1. Create Filament admin user: docker-compose exec app php artisan make:filament-user"
echo "   2. Visit http://localhost:8000/admin to access the admin panel"
echo "   3. Start developing your workspace-scoped application!"
echo ""
print_status "Happy coding! 🎉"