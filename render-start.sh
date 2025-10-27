#!/usr/bin/env bash
set -o errexit
set -o pipefail
set -o nounset

php artisan config:clear
php artisan config:cache
php artisan event:cache || true
php artisan view:clear
php artisan view:cache
php artisan storage:link || true
php artisan migrate --force

exec apache2-foreground
