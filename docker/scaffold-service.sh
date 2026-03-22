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
rm -f tests/Feature/ExampleTest.php
rm -f tests/Unit/ExampleTest.php

if [ -d "${SERVICE_DIR}/overlay" ]; then
  cp -R "${SERVICE_DIR}/overlay"/. "${TARGET_DIR}"/
fi

if [ -f .env.example ]; then
  cp .env.example .env
fi

cat > bootstrap/app.php <<'EOF'
<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('api/*') || $request->expectsJson() || $request->wantsJson()) {
                return null;
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApiRequest = static fn ($request): bool => $request->is('api/*') || $request->expectsJson() || $request->wantsJson();

        $exceptions->render(function (AuthenticationException $exception, $request) {
            if (($request->is('api/*') || $request->expectsJson() || $request->wantsJson())) {
                return response()->json([
                    'message' => 'Authentication required. Please login first and send a valid Bearer token.',
                    'error' => 'unauthenticated',
                ], Response::HTTP_UNAUTHORIZED);
            }

            return null;
        });

        $exceptions->render(function (ValidationException $exception, $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'message' => 'Some of the submitted data is invalid. Please review the highlighted fields and try again.',
                    'error' => 'validation_failed',
                    'errors' => $exception->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return null;
        });

        $renderMissingModel = static function (ModelNotFoundException $exception, $request) {
            $model = Str::of(class_basename($exception->getModel() ?? 'record'))
                ->snake(' ')
                ->lower()
                ->toString();

            $route = $request->route();
            $parameters = collect($route?->parameters() ?? [])
                ->reject(fn ($value, $key) => in_array($key, ['page', 'per_page'], true))
                ->filter(fn ($value) => is_scalar($value) && $value !== '')
                ->map(fn ($value, $key) => [
                    'key' => (string) $key,
                    'value' => (string) $value,
                ])
                ->values();

            $message = "No {$model} found for the requested lookup.";

            if ($parameters->isNotEmpty()) {
                $parameter = $parameters->first();
                $label = Str::of($parameter['key'])->snake(' ')->lower()->toString();
                $message = "No {$model} found with {$label} '{$parameter['value']}'.";
            }

            return response()->json([
                'message' => $message,
                'error' => 'resource_not_found',
            ], Response::HTTP_NOT_FOUND);
        };

        $exceptions->render(function (ModelNotFoundException $exception, $request) use ($isApiRequest, $renderMissingModel) {
            if ($isApiRequest($request)) {
                return $renderMissingModel($exception, $request);
            }

            return null;
        });

        $exceptions->render(function (NotFoundHttpException $exception, $request) use ($isApiRequest, $renderMissingModel) {
            if ($isApiRequest($request)) {
                $previous = $exception->getPrevious();

                if ($previous instanceof ModelNotFoundException) {
                    return $renderMissingModel($previous, $request);
                }

                return response()->json([
                    'message' => 'The requested API endpoint could not be found.',
                    'error' => 'route_not_found',
                ], Response::HTTP_NOT_FOUND);
            }

            return null;
        });

        $exceptions->render(function (QueryException $exception, $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'message' => 'The service could not complete the request because of a data access problem.',
                    'error' => 'database_error',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return null;
        });

        $exceptions->render(function (\Throwable $exception, $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'message' => 'Something went wrong while processing the request. Please try again.',
                    'error' => 'internal_server_error',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return null;
        });
    })->create();
EOF

if [ -n "${COMPOSER_REQUIRE:-}" ]; then
  composer require --no-interaction --no-progress ${COMPOSER_REQUIRE}
fi

if [ -n "${COMPOSER_REQUIRE_DEV:-}" ]; then
  composer require --dev --no-interaction --no-progress ${COMPOSER_REQUIRE_DEV}
fi

if printf '%s' "${COMPOSER_REQUIRE_DEV:-}" | grep -q 'laravel/telescope'; then
  if [ ! -f vendor/laravel/telescope/src/TelescopeServiceProvider.php ]; then
    composer require --dev --no-interaction --no-progress laravel/telescope:^5.0
  fi

  php artisan vendor:publish --tag=telescope-assets --force >/dev/null 2>&1 || true
fi

if [ "${PASSPORT_ENABLED:-false}" = "true" ]; then
  php artisan vendor:publish --provider="Laravel\\Passport\\PassportServiceProvider" --tag=migrations --force || true
fi

composer dump-autoload --optimize
