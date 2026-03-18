#!/usr/bin/env sh
set -e

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
  php artisan key:generate --force >/dev/null 2>&1 || true
fi

php artisan migrate --force
php artisan optimize:clear || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec php-fpm

