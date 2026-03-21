<?php

namespace App\Providers;

use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\UserProjectionRepositoryInterface;
use App\Repositories\Eloquent\AssignmentRepository;
use App\Repositories\Eloquent\UserProjectionRepository;
use App\Support\CircuitBreaker\CircuitBreaker;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AssignmentRepositoryInterface::class, AssignmentRepository::class);
        $this->app->bind(UserProjectionRepositoryInterface::class, UserProjectionRepository::class);

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
}
