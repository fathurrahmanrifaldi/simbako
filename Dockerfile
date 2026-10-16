# ==============================================================================
# Stage 1: Build Frontend Assets (Vite + Tailwind CSS)
# ==============================================================================
FROM node:22-alpine AS frontend
WORKDIR /app

# Cache dependency layer
COPY package*.json ./
RUN npm ci

# Copy build config dan source assets
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

# Compile production assets ke public/build
RUN npm run build

# ==============================================================================
# Stage 2: Install PHP Composer Dependencies
# ==============================================================================
FROM composer:2.8 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# ==============================================================================
# Stage 3: Production Runtime (FrankenPHP + PHP 8.4 Alpine)
# ==============================================================================
FROM dunglas/frankenphp:1-php8.4-alpine

WORKDIR /app

# Salin composer CLI untuk classmap generation
COPY --from=vendor /usr/bin/composer /usr/bin/composer

# Install ekstensi PHP yang dibutuhkan Laravel & MySQL
RUN install-php-extensions \
    pdo_mysql \
    bcmath \
    intl \
    zip \
    opcache \
    gd

# Environment default
ENV APP_ENV=production \
    APP_DEBUG=false \
    SERVER_NAME=":${PORT:-80}"

# Copy source code project
COPY . .

# Copy build artifacts dari Stage 1 & Stage 2
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

# Copy konfigurasi Caddy & Entrypoint
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Normalisasi newline (Windows CRLF safety) dan permissions
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Generate optimized autoload classmap
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
