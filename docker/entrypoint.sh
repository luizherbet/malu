#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --force --no-interaction
fi

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if [ ! -f public/build/manifest.json ]; then
    if command -v npm >/dev/null 2>&1; then
        npm ci --ignore-scripts 2>/dev/null || npm install --ignore-scripts
        npm run build
    fi
fi

mkdir -p storage/framework/{cache,sessions,views} storage/app/private/downloads
touch database/database.sqlite 2>/dev/null || true
chmod -R u+rwX storage bootstrap/cache database 2>/dev/null || true

php artisan migrate --force --no-interaction

exec "$@"
