#!/usr/bin/env bash
set -e
cd /var/www/html

echo "Running composer..."
composer install --no-dev --no-interaction --prefer-dist

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Running migrations..."
php artisan migrate --force

exec "$@"
