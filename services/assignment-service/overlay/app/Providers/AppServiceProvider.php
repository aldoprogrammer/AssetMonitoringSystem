<?php

namespace App\Providers;

use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\UserProjectionRepositoryInterface;
use App\Repositories\Eloquent\AssignmentRepository;
use App\Repositories\Eloquent\UserProjectionRepository;
use App\Support\CircuitBreaker\CircuitBreaker;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider as LaravelTelescopeServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AssignmentRepositoryInterface::class, AssignmentRepository::class);
        $this->app->bind(UserProjectionRepositoryInterface::class, UserProjectionRepository::class);

        $this->registerTelescope();

        $this->app->singleton(CircuitBreaker::class, function ($app): CircuitBreaker {
            /** @var CacheFactory $cache */
            $cache = $app->make(CacheFactory::class);

            return new CircuitBreaker(
                $cache->store(),
                'inventory-service',
                (int) config('services.circuit_breaker.failure_threshold'),
                (int) config('services.circuit_breaker.open_seconds'),
            );
        });
    }

    public function boot(): void
    {
    }

    private function registerTelescope(): void
    {
        if (! $this->app->environment('local') || ! class_exists(LaravelTelescopeServiceProvider::class)) {
            return;
        }

        $this->app->register(LaravelTelescopeServiceProvider::class);
        $this->app->register(TelescopeServiceProvider::class);
    }
}
