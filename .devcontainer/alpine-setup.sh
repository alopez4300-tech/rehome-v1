#!/bin/bash
set -e

echo "🔧 Fallback setup for Alpine Linux detected..."

# Install system dependencies for Alpine
echo "📦 Installing Alpine packages..."
sudo apk update
sudo apk add --no-cache \
    mysql-client \
    unzip \
    curl \
    wget \
    bash \
    git \
    openssh-client

# Install Composer
echo "📦 Installing Composer..."
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Install Node.js and npm (if not already installed by features)
if ! command -v node >/dev/null 2>&1; then
    echo "📦 Installing Node.js..."
    sudo apk add --no-cache nodejs npm
fi

# Set proper permissions and continue with main setup
sudo chown -R vscode:vscode /workspace || sudo chown -R $(whoami):$(whoami) /workspace
cd /workspace

echo "✅ Alpine Linux setup complete, continuing with main setup..."

# Continue with the main setup process
bash .devcontainer/post-create.sh