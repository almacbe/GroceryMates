#!/usr/bin/env bash
set -o errexit
set -o pipefail
set -o nounset

php -d detect_unicode=0 <<'PHP'
$key = getenv('APP_KEY') ?: '';
if ($key === '') {
    fwrite(STDERR, \"APP_KEY is not set. Configure it in Render's env vars.\\n\");
    exit(1);
}

if (str_starts_with($key, 'base64:')) {
    $decoded = base64_decode(substr($key, 7), true);
    if ($decoded === false || strlen($decoded) !== 32) {
        fwrite(STDERR, \"Invalid base64 APP_KEY detected.\\n\");
        exit(1);
    }
    exit(0);
}

if (strlen($key) !== 32) {
    fwrite(STDERR, \"APP_KEY must be 32 bytes for AES-256-CBC.\\n\");
    exit(1);
}
PHP

php artisan config:clear
php artisan config:cache
php artisan event:cache || true
php artisan view:clear
php artisan view:cache
php artisan storage:link || true
php artisan migrate --force

exec apache2-foreground
