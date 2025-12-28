FROM php:8.4-fpm

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

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory permissions
RUN chown -R www-data:www-data /var/www/html

# Copy application files
COPY --chown=www-data:www-data . /var/www/html

# Install dependencies
USER www-data
RUN composer install --no-dev --optimize-autoloader --no-interaction
USER root

# Create supervisor directories
RUN mkdir -p /var/log/supervisor /var/run /etc/supervisor/conf.d
RUN chown -R www-data:www-data /var/log/supervisor /var/run

# Copy supervisor configuration
COPY --chown=www-data:www-data config/supervisor/ /var/www/html/config/supervisor/
COPY --chown=www-data:www-data config/supervisor/ /etc/supervisor/conf.d/

# Create storage and logs directories
RUN mkdir -p /var/www/html/storage/logs/supervisor \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage

# Copy PHP-FPM configuration
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/var/www/html/config/supervisor/supervisord.conf", "-n"]

