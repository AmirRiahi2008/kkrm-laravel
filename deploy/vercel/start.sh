#!/bin/sh
set -eu

cd /var/www/html

: "${APP_KEY:?Configure APP_KEY in Vercel}"
: "${DB_HOST:?Configure DB_HOST in Vercel}"
: "${DB_DATABASE:?Configure DB_DATABASE in Vercel}"
: "${DB_USERNAME:?Configure DB_USERNAME in Vercel}"
: "${AWS_ACCESS_KEY_ID:?Configure AWS_ACCESS_KEY_ID in Vercel}"
: "${AWS_SECRET_ACCESS_KEY:?Configure AWS_SECRET_ACCESS_KEY in Vercel}"
: "${AWS_DEFAULT_REGION:?Configure AWS_DEFAULT_REGION in Vercel}"
: "${AWS_BUCKET:?Configure AWS_BUCKET in Vercel}"

if [ "${MEDIA_DISK:-}" != "s3" ]; then
    printf '%s\n' 'Set MEDIA_DISK=s3 after migrating existing media to persistent storage.' >&2
    exit 1
fi

if [ "${SESSION_DRIVER:-database}" != "database" ] || [ "${CACHE_STORE:-database}" != "database" ]; then
    printf '%s\n' 'Use database-backed sessions and cache in this deployment.' >&2
    exit 1
fi

if { [ "${VERCEL_ENV:-}" = "preview" ] || [ -z "${APP_URL:-}" ]; } && [ -n "${VERCEL_URL:-}" ]; then
    export APP_URL="https://${VERCEL_URL}"
fi

: "${APP_URL:?Configure APP_URL in Vercel}"

php deploy/vercel/validate.php

export APP_URL="${APP_URL%/}"
export APP_ENV=production
export APP_DEBUG=false
export FRONTEND_URL="${APP_URL}"
export CORS_ALLOWED_ORIGINS="${APP_URL}"
export SESSION_DRIVER=database
export SESSION_DOMAIN=null
export SESSION_SECURE_COOKIE=true
export SESSION_HTTP_ONLY=true
export SESSION_ENCRYPT=true
export SESSION_SAME_SITE=lax
export CACHE_STORE=database
export QUEUE_CONNECTION=sync
export FILESYSTEM_DISK=s3
export MEDIA_MAX_KB="${MEDIA_MAX_KB:-3840}"
export LOG_CHANNEL=stderr
export LARAVEL_STORAGE_PATH=/tmp/kkrm/storage
export VIEW_COMPILED_PATH=/tmp/kkrm/storage/framework/views
export APP_CONFIG_CACHE=/tmp/kkrm/bootstrap/config.php
export APP_SERVICES_CACHE=/tmp/kkrm/bootstrap/services.php
export APP_PACKAGES_CACHE=/tmp/kkrm/bootstrap/packages.php
export APP_EVENTS_CACHE=/tmp/kkrm/bootstrap/events.php
export APP_ROUTES_CACHE=/tmp/kkrm/bootstrap/routes.php

app_host="$(php -r '$url = parse_url(getenv("APP_URL")); echo $url["host"].(isset($url["port"]) ? ":".$url["port"] : "");')"
export SANCTUM_STATEFUL_DOMAINS="${SANCTUM_STATEFUL_DOMAINS:-},${app_host}"

mkdir -p /tmp/kkrm/bootstrap /tmp/kkrm/storage/framework/cache/data /tmp/kkrm/storage/framework/sessions /tmp/kkrm/storage/framework/views /tmp/kkrm/storage/logs
chown -R www-data:www-data /tmp/kkrm

php artisan config:cache
php artisan view:cache

exec docker-php-entrypoint "$@"
