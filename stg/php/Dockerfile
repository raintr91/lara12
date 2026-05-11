# Docker Hub
#FROM library/php:8.2-fpm
# AWS ECR Pull Through Cache
FROM 182399679812.dkr.ecr.ap-northeast-1.amazonaws.com/docker-hub/library/php:8.2-fpm

# apt-get
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
  zlib1g-dev \
  libzip-dev \
  gnupg2 \
  libpng-dev \
  libjpeg-dev \
  libgif-dev \
  libfreetype6-dev \
  libmagickwand-dev --no-install-recommends \
  && docker-php-ext-install zip pdo_mysql bcmath \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) gd \
  && pecl install imagick \
  && docker-php-ext-enable imagick \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

# Composer Install
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# ENV
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /composer
ENV PATH $PATH:/composer/vendor/bin

# COPY
COPY ./stg/php/php.ini /usr/local/etc/php/php.ini
#COPY ./stg/php/www.conf /usr/local/etc/php-fpm.d/www.conf

COPY ./src /var/www/laravel

# WORKDIR
WORKDIR /var/www/laravel

ARG COMPOSER_GITHUB_TOKEN=""

# Laravel
RUN set -eu && \
  if [ -n "${COMPOSER_GITHUB_TOKEN}" ]; then \
    export COMPOSER_AUTH="{\"github-oauth\":{\"github.com\":\"${COMPOSER_GITHUB_TOKEN}\"}}"; \
  fi && \
  php -d memory_limit=-1 /usr/bin/composer install --no-dev && \
  composer global require laravel/installer && \
  chmod 775 public && \
  chown root:www-data public && \
  chmod -R a+w storage/ bootstrap/cache && \
#  npm install && \
  php artisan config:clear && \
#  php artisan cache:clear && \ # Illuminate\Database\QueryException
  php artisan route:clear && \
  php artisan view:clear

# Port
EXPOSE 9000

# ECS Bind Mounts
VOLUME [ "/var/www/laravel" ]

#CMD [ "php-fpm", "-F" ]
