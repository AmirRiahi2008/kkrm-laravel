#!/bin/sh
set -eu
cd /var/www/html
if [ -z "${APP_KEY:-}" ]; then
    printf '%s\n' 'APP_KEY must be configured in the Vercel project.' >&2
    exit 1
fi
if [ -z "${DB_HOST:-}" ] || [ -z "${DB_DATABASE:-}" ] || [ -z "${DB_USERNAME:-}" ]; then
    printf '%s\n' 'Configure the external MySQL connection before deploying.' >&2
    exit 1
fi
if [ "${MEDIA_DISK:-local}" = "local" ] || [ "${MEDIA_DISK:-local}" = "public" ]; then
    printf '%s\n' 'MEDIA_DISK must use persistent external storage on Vercel.' >&2
    exit 1
fi
if { [ "${VERCEL_ENV:-}" = "preview" ] || [ -z "${APP_URL:-}" ]; } && [ -n "${VERCEL_URL:-}" ]; then
    export APP_URL="https://${VERCEL_URL}"
fi
export FRONTEND_URL="${APP_URL}"
export CORS_ALLOWED_ORIGINS="${APP_URL}"
app_host="$(php -r '$url = parse_url(getenv("APP_URL")); echo $url["host"].(isset($url["port"]) ? ":".$url["port"] : "");')"
export SANCTUM_STATEFUL_DOMAINS="${SANCTUM_STATEFUL_DOMAINS:-},${app_host}"
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage/framework storage/logs bootstrap/cache
php artisan config:cache
exec docker-php-entrypoint "$@"
