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

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  echo "Generating application key..."
  php artisan key:generate --force >/dev/null 2>&1 || true
fi

if [ "${PASSPORT_ENABLED:-false}" = "true" ]; then
  if [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
    echo "Generating Passport encryption keys..."
    php artisan passport:keys --force || true
  fi
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "Running database migrations..."
  php artisan migrate --force
fi

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
  echo "Running database seeders..."
  php artisan db:seed --force
fi

if [ "${PASSPORT_ENABLED:-false}" = "true" ] && [ "${RUN_PASSPORT_CLIENT_SETUP:-false}" = "true" ]; then
  echo "Ensuring Passport personal access client exists..."
  if ! php artisan passport:client --personal --name="Asset Monitoring System Personal Access Client" --no-interaction; then
    true
  fi
fi

exec "$@"
