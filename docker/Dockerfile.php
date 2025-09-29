FROM php:8.3-fpm

# Install system dependencies for complete Laravel + Filament stack
RUN apt-get update && apt-get install -y \
    libpq-dev \
    redis-server \
    nodejs \
    npm \
    git \
    unzip \
    curl \
    libcurl4-openssl-dev \
    zip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libicu-dev \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions for our complete Laravel + Filament stack
RUN docker-php-ext-configure intl \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    zip \
    xml \
    curl \
    fileinfo

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create app user
RUN useradd -G www-data,root -u 1000 -d /home/app app
RUN mkdir -p /home/app/.composer && \
    chown -R app:app /home/app

# Set working directory
WORKDIR /var/www/html

# Switch to app user
USER app

# Default command (can be overridden)
CMD ["php-fpm"]