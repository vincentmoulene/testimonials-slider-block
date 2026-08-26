# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Single-image production build: FrankenPHP (Caddy + PHP) serves the app with
# HTTP/2, automatic HTTPS and a built-in HTTP cache-friendly static file layer.
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.4 AS base

RUN install-php-extensions \
        gd \
        intl \
        opcache \
        zip \
    && rm -rf /tmp/*

WORKDIR /app

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    SERVER_NAME=:80

COPY --from=composer/composer:2-bin /composer /usr/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini

# ---------------------------------------------------------------------------
# Dependencies (cached as long as composer.json/lock do not change)
# ---------------------------------------------------------------------------
FROM base AS vendor

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# ---------------------------------------------------------------------------
# Application
# ---------------------------------------------------------------------------
FROM base AS app

COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN composer dump-autoload --classmap-authoritative --no-dev \
    && php bin/console cache:warmup \
    && php bin/console asset-map:compile \
    && chown -R www-data:www-data var public/assets

# The public directory is the document root; everything else stays private.
ENV FRANKENPHP_CONFIG="worker ./public/index.php"

EXPOSE 80
EXPOSE 443

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s \
    CMD curl -fsS http://127.0.0.1/robots.txt > /dev/null || exit 1
