#!/bin/sh
set -eu

cd /var/www/app

if [ ! -f .env ]; then
  cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force >/dev/null 2>&1 || true
fi

if [ "${PASSPORT_ENABLED:-false}" = "true" ]; then
  if [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
    php artisan passport:keys --force >/dev/null 2>&1 || true
  fi
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force
fi

exec "$@"
