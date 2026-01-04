# ============================================
# Stage 1: Base PHP with extensions
# ============================================
FROM php:8.4-fpm AS php-base

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif bcmath gd

RUN docker-php-source extract \
    && cd /usr/src/php/ext/pcntl \
    && phpize \
    && ./configure \
    && make -j$(nproc) \
    && make install \
    && docker-php-ext-enable pcntl \
    && docker-php-source delete

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini



# ============================================
# Stage 2: Node.js build stage (Wayfinder + Vite)
# ============================================
FROM node:20-alpine AS node-build

RUN apk add --no-cache \
    php php-cli php-phar php-mbstring php-xml php-json php-openssl php-tokenizer php-dom \
    php-iconv php-session php-fileinfo php-pcntl php-posix php-simplexml php-xmlwriter \
    php-pcntl php-pdo

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /build

COPY composer.json composer.lock artisan ./
COPY app/ ./app/
COPY bootstrap/ ./bootstrap/
COPY config/ ./config/
COPY routes/ ./routes/

RUN --mount=type=cache,target=/root/.composer \
    composer install --no-scripts --optimize-autoloader --no-interaction --prefer-dist

RUN echo "APP_KEY=base64:dummy" > .env

RUN mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs

COPY package.json package-lock.json ./

RUN --mount=type=cache,target=/root/.npm \
    npm ci

COPY resources/ ./resources/
COPY vite.config.ts tsconfig.json eslint.config.js ./
COPY public/ ./public/

COPY storage/ ./storage/

RUN mkdir -p bootstrap/cache \
    && mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs

RUN php artisan wayfinder:generate --with-form

RUN test -f resources/js/routes/dashboard/articles/index.ts || \
    (echo "ERROR: Wayfinder routes not generated!" && exit 1)

RUN npm run build



# ============================================
# Stage 3: Final PHP-FPM runtime
# ============================================
FROM php-base AS final

WORKDIR /var/www/html

COPY composer.json composer.lock artisan ./
COPY app/ ./app/
COPY bootstrap/ ./bootstrap/
COPY config/ ./config/
COPY routes/ ./routes/

RUN --mount=type=cache,target=/root/.composer \
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

COPY --chown=www-data:www-data . .

COPY --from=node-build --chown=www-data:www-data /build/public/build ./public/build
COPY --from=node-build --chown=www-data:www-data /build/resources/js/routes ./resources/js/routes
COPY --from=node-build --chown=www-data:www-data /build/resources/js/actions ./resources/js/actions
COPY --from=node-build --chown=www-data:www-data /build/resources/js/wayfinder ./resources/js/wayfinder

# Copy build assets to backup location for volume initialization
RUN cp -r public/build public/build.bak || true

RUN mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
