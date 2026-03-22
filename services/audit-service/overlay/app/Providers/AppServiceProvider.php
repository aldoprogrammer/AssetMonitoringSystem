<?php

namespace App\Providers;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Eloquent\AuditLogRepository;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider as LaravelTelescopeServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogRepository::class);

        $this->registerTelescope();
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
