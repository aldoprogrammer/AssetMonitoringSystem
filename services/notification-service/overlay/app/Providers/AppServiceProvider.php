<?php

namespace App\Providers;

use App\Repositories\Contracts\NotificationDeliveryRepositoryInterface;
use App\Repositories\Eloquent\NotificationDeliveryRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NotificationDeliveryRepositoryInterface::class, NotificationDeliveryRepository::class);
    }

    public function boot(): void
    {
    }
}
