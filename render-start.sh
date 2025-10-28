#!/usr/bin/env bash
set -o errexit
set -o pipefail
set -o nounset

should_refresh_key() {
    php -d detect_unicode=0 <<'PHP'
$key = getenv('APP_KEY') ?: '';
if ($key === '' || $key === 'base64:PLACEHOLDER_SET_IN_RENDER_DASHBOARD') {
    exit(1);
}

if (str_starts_with($key, 'base64:')) {
    $decoded = base64_decode(substr($key, 7), true);
    if ($decoded === false || strlen($decoded) !== 32) {
        exit(1);
    }
    exit(0);
}

// Non base64 keys must be 32 bytes for AES-256-CBC.
if (strlen($key) !== 32) {
    exit(1);
}

exit(0);
PHP
}

if ! should_refresh_key; then
    export APP_KEY="$(php artisan key:generate --show)"
fi

php artisan config:clear
php artisan config:cache
php artisan event:cache || true
php artisan view:clear
php artisan view:cache
php artisan storage:link || true
php artisan migrate --force

exec apache2-foreground
