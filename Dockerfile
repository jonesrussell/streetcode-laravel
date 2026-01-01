# ============================================
# Stage 1: Base PHP with extensions
# ============================================
FROM php:8.4-fpm AS php-base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ============================================
# Stage 2: Node.js build stage (with PHP for wayfinder)
# ============================================
FROM php-base AS node-build

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /build

# Copy composer files first for wayfinder generation
COPY composer.json composer.lock artisan ./
COPY app/ ./app/
COPY bootstrap/ ./bootstrap/
COPY config/ ./config/
COPY routes/ ./routes/

# Install composer dependencies (needed for wayfinder)
ENV COMPOSER_PROCESS_TIMEOUT=0
ENV COMPOSER_DISABLE_XDEBUG_WARN=1
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Create storage directories and set permissions for wayfinder
RUN mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/testing \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs \
    && chmod -R 775 storage

# Create minimal .env for wayfinder generation (wayfinder doesn't need DB connection)
COPY .env .env

# Copy package files
COPY package.json package-lock.json ./

# Install npm dependencies
RUN npm ci

# Copy files needed for build
COPY resources/ ./resources/
COPY vite.config.ts tsconfig.json ./
COPY eslint.config.js ./
COPY public/ ./public/

# Pre-generate wayfinder files (wayfinder vite plugin will use these)
RUN php artisan wayfinder:generate --with-form || echo "Wayfinder generation failed, continuing..."

# Build assets
RUN npm run build

# ============================================
# Stage 3: Final PHP-FPM runtime
# ============================================
FROM php-base AS final

WORKDIR /var/www/html

# Copy only composer files and minimal app structure for better layer caching
# artisan, app/, bootstrap/, config/, and routes/ are needed for composer post-install scripts
COPY composer.json composer.lock artisan ./
COPY app/ ./app/
COPY bootstrap/ ./bootstrap/
COPY config/ ./config/
COPY routes/ ./routes/

# Install dependencies as root (composer needs write access to vendor)
# Disable parallel downloads to avoid concurrent process issues
ENV COMPOSER_PROCESS_TIMEOUT=0
ENV COMPOSER_DISABLE_XDEBUG_WARN=1
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copy rest of application files (excluding public/build which we'll copy from build stage)
COPY --chown=www-data:www-data . /var/www/html

# Copy built assets from node-build stage (after copying app files to preserve them)
COPY --from=node-build --chown=www-data:www-data /build/public/build ./public/build

# Fix ownership after copying files
RUN chown -R www-data:www-data /var/www/html

# Create storage directories with proper structure
RUN mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/testing \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage

# Copy PHP-FPM configuration
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Start PHP-FPM
CMD ["php-fpm"]
