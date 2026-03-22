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
