#!/bin/sh
set -eu

cd /var/www/app

if [ ! -f .env ]; then
  cp .env.example .env
fi

upsert_env() {
  key="$1"
  value="$2"

  if [ -z "${value}" ]; then
    return 0
  fi

  escaped_value=$(printf '%s' "${value}" | sed 's/[\/&]/\\&/g')

  if grep -q "^${key}=" .env 2>/dev/null; then
    sed -i "s/^${key}=.*/${key}=${escaped_value}/" .env
  else
    printf '\n%s=%s\n' "${key}" "${value}" >> .env
  fi
}

sync_overlay() {
  if [ -d /opt/service-overlay ]; then
    cp -a /opt/service-overlay/. /var/www/app/
  fi

  # Never keep stale compiled caches in local/dev containers.
  rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php bootstrap/cache/events.php 2>/dev/null || true
  find bootstrap/cache -maxdepth 1 -type f -name 'routes-*.php' -delete 2>/dev/null || true
}

start_overlay_sync_loop() {
  if [ ! -d /opt/service-overlay ]; then
    return 0
  fi

  (
    while true; do
      sync_overlay
      sleep 1
    done
  ) &
}

upsert_env "APP_ENV" "${APP_ENV:-}"
upsert_env "APP_DEBUG" "${APP_DEBUG:-}"
upsert_env "APP_URL" "${APP_URL:-}"
upsert_env "LOG_CHANNEL" "${LOG_CHANNEL:-}"
upsert_env "DB_CONNECTION" "${DB_CONNECTION:-}"
upsert_env "DB_HOST" "${DB_HOST:-}"
upsert_env "DB_PORT" "${DB_PORT:-}"
upsert_env "DB_DATABASE" "${DB_DATABASE:-}"
upsert_env "DB_USERNAME" "${DB_USERNAME:-}"
upsert_env "DB_PASSWORD" "${DB_PASSWORD:-}"
upsert_env "CACHE_STORE" "${CACHE_STORE:-}"
upsert_env "SESSION_DRIVER" "${SESSION_DRIVER:-}"
upsert_env "QUEUE_CONNECTION" "${QUEUE_CONNECTION:-}"
upsert_env "TELESCOPE_ENABLED" "${TELESCOPE_ENABLED:-}"
upsert_env "TELESCOPE_RECORD_ALL" "${TELESCOPE_RECORD_ALL:-}"
upsert_env "TELESCOPE_PATH" "${TELESCOPE_PATH:-}"
upsert_env "TELESCOPE_SERVICE_NAME" "${TELESCOPE_SERVICE_NAME:-}"
upsert_env "RABBITMQ_HOST" "${RABBITMQ_HOST:-}"
upsert_env "RABBITMQ_PORT" "${RABBITMQ_PORT:-}"
upsert_env "RABBITMQ_USER" "${RABBITMQ_USER:-}"
upsert_env "RABBITMQ_PASSWORD" "${RABBITMQ_PASSWORD:-}"
upsert_env "RABBITMQ_VHOST" "${RABBITMQ_VHOST:-}"
upsert_env "RABBITMQ_EXCHANGE" "${RABBITMQ_EXCHANGE:-}"
upsert_env "RABBITMQ_QUEUE_PREFIX" "${RABBITMQ_QUEUE_PREFIX:-}"
upsert_env "MAIL_MAILER" "${MAIL_MAILER:-}"
upsert_env "MAIL_HOST" "${MAIL_HOST:-}"
upsert_env "MAIL_PORT" "${MAIL_PORT:-}"
upsert_env "MAIL_FROM_ADDRESS" "${MAIL_FROM_ADDRESS:-}"
upsert_env "MAIL_FROM_NAME" "${MAIL_FROM_NAME:-}"
upsert_env "SLACK_WEBHOOK_URL" "${SLACK_WEBHOOK_URL:-}"
upsert_env "INVENTORY_SERVICE_BASE_URL" "${INVENTORY_SERVICE_BASE_URL:-}"
upsert_env "CIRCUIT_BREAKER_FAILURE_THRESHOLD" "${CIRCUIT_BREAKER_FAILURE_THRESHOLD:-}"
upsert_env "CIRCUIT_BREAKER_OPEN_SECONDS" "${CIRCUIT_BREAKER_OPEN_SECONDS:-}"
upsert_env "CIRCUIT_BREAKER_TIMEOUT_SECONDS" "${CIRCUIT_BREAKER_TIMEOUT_SECONDS:-}"
upsert_env "DEVICE_INACTIVE_AFTER_MINUTES" "${DEVICE_INACTIVE_AFTER_MINUTES:-}"

sync_overlay
start_overlay_sync_loop

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  echo "Generating application key..."
  php artisan key:generate --force >/dev/null 2>&1 || true
fi

APP_ENV_VALUE="${APP_ENV:-production}"
RUN_MIGRATIONS_VALUE="${RUN_MIGRATIONS:-}"
RUN_SEEDERS_VALUE="${RUN_SEEDERS:-}"
RUN_PASSPORT_CLIENT_SETUP_VALUE="${RUN_PASSPORT_CLIENT_SETUP:-}"

if [ -z "${RUN_MIGRATIONS_VALUE}" ] && [ "${APP_ENV_VALUE}" = "local" ]; then
  RUN_MIGRATIONS_VALUE="true"
fi

if [ -z "${RUN_SEEDERS_VALUE}" ] && [ "${APP_ENV_VALUE}" = "local" ] && [ "${PASSPORT_ENABLED:-false}" = "true" ]; then
  RUN_SEEDERS_VALUE="true"
fi

if [ -z "${RUN_PASSPORT_CLIENT_SETUP_VALUE}" ] && [ "${APP_ENV_VALUE}" = "local" ] && [ "${PASSPORT_ENABLED:-false}" = "true" ]; then
  RUN_PASSPORT_CLIENT_SETUP_VALUE="true"
fi

if [ "${PASSPORT_ENABLED:-false}" = "true" ]; then
  if [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
    echo "Generating Passport encryption keys..."
    php artisan passport:keys --force || true
  fi
fi

if [ "${RUN_MIGRATIONS_VALUE}" = "true" ]; then
  echo "Running database migrations..."
  php artisan migrate --force
fi

if [ "${RUN_SEEDERS_VALUE}" = "true" ]; then
  echo "Running database seeders..."
  php artisan db:seed --force
fi

if [ "${PASSPORT_ENABLED:-false}" = "true" ] && [ "${RUN_PASSPORT_CLIENT_SETUP_VALUE}" = "true" ]; then
  echo "Ensuring Passport personal access client exists..."
  php artisan passport:client --personal --provider=users --name="Asset Monitoring System Personal Access Client" --no-interaction >/dev/null 2>&1 || true
fi

exec "$@"
