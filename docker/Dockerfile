# api/docker/Dockerfile — một file cho local (dev) và CI deploy.
#
# Local:  docker compose build  → target dev
# CI:     docker build --target deploy -f docker/Dockerfile .
#         docker build --target nginx-deploy -f docker/Dockerfile .

# ── PHP base (extension dùng chung dev + deploy)
FROM php:8.4-fpm AS php-base

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libpq-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libssl-dev \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql bcmath pcntl zip intl gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/composer \
    PATH="/composer/vendor/bin:${PATH}"

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini

# ── dev: bind mount source — không COPY app, không composer install lúc build
FROM php-base AS dev

RUN pecl install redis pcov \
    && docker-php-ext-enable redis pcov

WORKDIR /var/www/api/src

# ── deploy: CI/ECS — bake vendor; Laravel .env inject lúc runtime
FROM php-base AS deploy

WORKDIR /var/www/api/src

COPY src/composer.json src/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-autoloader

COPY src ./
RUN composer dump-autoload --optimize --no-dev \
    && php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear

EXPOSE 9000
VOLUME ["/var/www/api/src"]

# ── nginx deploy (ECS sidecar — PHP-FPM cùng task, fastcgi 127.0.0.1:9000)
FROM nginx:1.27-alpine AS nginx-deploy

COPY docker/nginx/default.deploy.conf /etc/nginx/conf.d/default.conf
