# syntax=docker/dockerfile:1

FROM php:8.4-cli-bookworm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    ffmpeg \
    git \
    unzip \
    libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Node.js required by yt-dlp for YouTube (EJS extractor)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

RUN curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp \
    -o /usr/local/bin/yt-dlp \
    && chmod a+rx /usr/local/bin/yt-dlp

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

FROM base AS development

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

FROM node:22-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

FROM base AS production

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/{cache,sessions,views} storage/app/private/downloads database \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

USER www-data

ENTRYPOINT ["entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
