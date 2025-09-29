#!/bin/bash
set -e

echo "🚀 Setting up Rehome full-stack development environment..."

# Debug system information
echo "🔍 System Information:"
echo "  OS: $(uname -a)"
echo "  User: $(whoami)"
echo "  Working Directory: $(pwd)"
echo "  Available package managers:"
command -v apt-get >/dev/null 2>&1 && echo "    - apt-get (Ubuntu/Debian)"
command -v apk >/dev/null 2>&1 && echo "    - apk (Alpine)"
command -v yum >/dev/null 2>&1 && echo "    - yum (RHEL/CentOS)"

# Detect OS and install dependencies accordingly
if command -v apt-get >/dev/null 2>&1; then
    echo "📦 Detected Ubuntu/Debian - using apt-get"
    sudo apt-get update
    sudo apt-get install -y mysql-client unzip curl wget
elif command -v apk >/dev/null 2>&1; then
    echo "📦 Detected Alpine Linux - using apk"
    sudo apk update
    sudo apk add --no-cache mysql-client unzip curl wget bash
else
    echo "⚠️  Unknown package manager - attempting to continue without additional packages"
fi

# Verify required tools
echo "🔍 Verifying required tools:"
for tool in php composer node npm curl wget; do
    if command -v $tool >/dev/null 2>&1; then
        echo "  ✅ $tool: $(command -v $tool)"
    else
        echo "  ❌ $tool: NOT FOUND"
    fi
done

# Install Laravel installer globally
composer global require laravel/installer

# Add composer global bin to PATH
echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> ~/.bashrc
export PATH="$HOME/.composer/vendor/bin:$PATH"

# Set proper permissions
sudo chown -R vscode:vscode /workspaces/rehome-v1
cd /workspaces/rehome-v1

# Create complete Laravel backend
echo "📦 Creating Laravel 11 backend..."
if [ ! -d "backend" ]; then
    echo "Creating fresh Laravel application..."
    composer create-project laravel/laravel:^11.0 backend --no-interaction
    cd backend
    
    # Install required packages
    echo "Installing backend packages..."
    composer require filament/filament:^3.0 --no-interaction
    composer require spatie/laravel-permission:^6.0 --no-interaction
    composer require laravel/sanctum --no-interaction
    composer require --dev laravel/pint --no-interaction
    composer require --dev phpstan/phpstan --no-interaction
    composer require --dev nunomaduro/collision --no-interaction
    
    # Publish and setup packages
    php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --no-interaction
    php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --no-interaction
    
    # Create complete backend structure
    bash /workspaces/rehome-v1/.devcontainer/create-backend-structure.sh
    
    cd ..
else
    echo "Backend directory exists, setting up dependencies..."
    cd backend
    composer install --no-interaction --prefer-dist --optimize-autoloader
    cd ..
fi

# Create complete React frontend
echo "🎨 Creating React 18 frontend..."
if [ ! -d "frontend" ]; then
    echo "Creating fresh React application..."
    npm create vite@latest frontend -- --template react-ts --yes
    cd frontend
    
    # Install frontend dependencies
    echo "Installing frontend packages..."
    npm install react-router-dom @tanstack/react-query axios lucide-react class-variance-authority clsx tailwindcss postcss autoprefixer zod
    npm install -D eslint @types/node @types/react @types/react-dom @typescript-eslint/parser @typescript-eslint/eslint-plugin eslint-config-prettier vite-tsconfig-paths vitest @vitest/ui @testing-library/react @testing-library/user-event @testing-library/dom jsdom happy-dom
    npm install -D storybook@~8.3 @storybook/react-vite @storybook/addon-essentials @storybook/addon-a11y @storybook/addon-interactions @storybook/test
    npm install -D @playwright/test
    npm install -D @lhci/cli
    
    # Setup Tailwind CSS
    npx tailwindcss init -p
    
    # Create complete frontend structure
    bash /workspaces/rehome-v1/.devcontainer/create-frontend-structure.sh
    
    cd ..
else
    echo "Frontend directory exists, installing dependencies..."
    cd frontend
    npm ci
    cd ..
fi

# Setup backend environment and database
echo "🗄️ Setting up backend environment..."
cd backend

# Create .env file
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Configure database connection
sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env
sed -i 's/# DB_HOST=127.0.0.1/DB_HOST=127.0.0.1/' .env
sed -i 's/# DB_PORT=3306/DB_PORT=3306/' .env
sed -i 's/# DB_DATABASE=laravel/DB_DATABASE=rehome/' .env
sed -i 's/# DB_USERNAME=root/DB_USERNAME=app/' .env
sed -i 's/# DB_PASSWORD=/DB_PASSWORD=app/' .env

# Generate application key
php artisan key:generate --no-interaction

# Set proper permissions
chmod -R 775 storage bootstrap/cache

# Wait for and setup database (with retries)
echo "Waiting for database to be ready..."
for i in {1..30}; do
    if php artisan migrate --force 2>/dev/null; then
        echo "✅ Database migrations completed"
        break
    else
        echo "⏳ Waiting for database... ($i/30)"
        sleep 2
    fi
done

# Seed database
php artisan db:seed --force 2>/dev/null || echo "⚠️  Database seeding skipped (will run later)"

cd ..

# Make scripts executable
chmod +x .devcontainer/post-create.sh
chmod +x .devcontainer/create-backend-structure.sh 2>/dev/null || true
chmod +x .devcontainer/create-frontend-structure.sh 2>/dev/null || true

echo ""
echo "✅ Rehome development environment setup complete!"
echo ""
echo "🌐 Your services will be available at:"
echo "  📱 Frontend (React):     http://localhost:3000"
echo "  🔧 Backend (Laravel):    http://localhost:8000"
echo "  👤 Admin Panel:          http://localhost:8000/admin"
echo "  📚 Storybook:           http://localhost:6006"
echo "  📧 MailHog:             http://localhost:8025"
echo ""
echo "👤 Admin login credentials:"
echo "  Email: admin1@rehome.build"
echo "  Password: password"
echo ""
echo "🛠️  Quick commands:"
echo "  make be          - Start Laravel backend server"
echo "  make fe          - Start React frontend dev server"
echo "  make storybook   - Start Storybook component library"
echo "  make ci          - Run all quality checks"
echo "  make up          - Start with Docker Compose"
echo ""
echo "🚀 Ready to code! The environment is fully automated."
echo ""