#!/bin/sh
set -e

HOST="${DB_HOST:-mysql}"
PORT="${DB_PORT:-3306}"
USER="${DB_USERNAME:-root}"
PASS="${DB_PASSWORD:-root}"
DBNAME="${DB_DATABASE:-evermed_central}"

# ── Wait for MySQL ─────────────────────────────────────────────────────────────
echo "[entrypoint] Waiting for MySQL at ${HOST}:${PORT}..."
until php -r "
try {
    new PDO('mysql:host=${HOST};port=${PORT};dbname=${DBNAME}', '${USER}', '${PASS}');
    exit(0);
} catch (Exception \$e) {
    exit(1);
}
" 2>/dev/null; do
  sleep 2
done
echo "[entrypoint] MySQL ready."

# ── Ensure storage directories exist (important when using volumes) ────────────
mkdir -p storage/framework/{sessions,views,cache} \
         storage/app/public \
         storage/logs \
         storage/fonts \
         bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# ── Run central migrations ─────────────────────────────────────────────────────
echo "[entrypoint] Running central migrations..."
php artisan migrate --force --no-interaction

# ── Run tenant migrations ──────────────────────────────────────────────────────
echo "[entrypoint] Running tenant migrations..."
php artisan tenants:migrate --force --no-interaction

# ── Public storage symlink ─────────────────────────────────────────────────────
php artisan storage:link --force 2>/dev/null || true

# ── Production caches ──────────────────────────────────────────────────────────
if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
fi

echo "[entrypoint] Starting: $*"
exec "$@"
