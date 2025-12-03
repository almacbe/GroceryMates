#!/usr/bin/env bash
set -euo pipefail

echo "➤ Installing PHP dependencies"
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "➤ Installing frontend dependencies"
npm ci --no-audit --no-fund

echo "➤ Building frontend assets"
npm run build

echo "➤ Running Laravel optimizations"
php artisan config:clear
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

echo "➤ Linking storage directory"
php artisan storage:link

echo "➤ Running database migrations"
php artisan migrate --force

echo "Deployment finished"
