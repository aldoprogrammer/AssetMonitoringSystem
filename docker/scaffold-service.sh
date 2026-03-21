#!/bin/sh
set -eu

SERVICE_DIR="${1:?service directory is required}"
TARGET_DIR="${2:?target directory is required}"
MANIFEST_FILE="${SERVICE_DIR}/manifest.env"

if [ ! -f "${MANIFEST_FILE}" ]; then
  echo "Missing manifest: ${MANIFEST_FILE}" >&2
  exit 1
fi

# shellcheck disable=SC1090
. "${MANIFEST_FILE}"

composer create-project laravel/laravel "${TARGET_DIR}" "^11.0" --prefer-dist --no-interaction

cd "${TARGET_DIR}"

# Remove Laravel's default user migration so each service can own its schema.
rm -f database/migrations/*_create_users_table.php
rm -f .env

if [ -d "${SERVICE_DIR}/overlay" ]; then
  cp -R "${SERVICE_DIR}/overlay"/. "${TARGET_DIR}"/
fi

if [ -f .env.example ]; then
  cp .env.example .env
fi

cat > bootstrap/app.php <<'EOF'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
EOF

if [ -n "${COMPOSER_REQUIRE:-}" ]; then
  composer require --no-interaction --no-progress ${COMPOSER_REQUIRE}
fi

if [ -n "${COMPOSER_REQUIRE_DEV:-}" ]; then
  composer require --dev --no-interaction --no-progress ${COMPOSER_REQUIRE_DEV}
fi

if [ "${PASSPORT_ENABLED:-false}" = "true" ]; then
  php artisan vendor:publish --provider="Laravel\\Passport\\PassportServiceProvider" --tag=migrations --force || true
fi

composer dump-autoload --optimize
