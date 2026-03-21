<?php

namespace App\Providers;

use App\Repositories\Contracts\DeviceHeartbeatRepositoryInterface;
use App\Repositories\Eloquent\DeviceHeartbeatRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DeviceHeartbeatRepositoryInterface::class, DeviceHeartbeatRepository::class);
    }

    public function boot(): void
    {
    }
}
