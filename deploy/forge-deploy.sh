#!/usr/bin/env bash
set -euo pipefail

echo "➤ Preparing writable directories"
mkdir -p bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs
chmod -R 775 bootstrap/cache storage

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

echo "➤ Checking database connectivity"
if ! php artisan db:show --json > /dev/null 2>&1; then
    echo ""
    echo "❌ ERROR: Cannot connect to database!"
    echo ""
    echo "Please check the following in Laravel Forge:"
    echo "  1. Create a MySQL database in Forge (Sites → Your Site → Database)"
    echo "  2. Update the .env file with correct credentials:"
    echo "     - DB_HOST (usually 127.0.0.1 for same-server database)"
    echo "     - DB_DATABASE (the database name you created)"
    echo "     - DB_USERNAME (usually 'forge')"
    echo "     - DB_PASSWORD (the password you set)"
    echo ""
    exit 1
fi

echo "➤ Running database migrations"
php artisan migrate --force

echo "Deployment finished"
