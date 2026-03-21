<?php

namespace App\Repositories\Eloquent;

use App\Models\NotificationDelivery;
use App\Repositories\Contracts\NotificationDeliveryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationDeliveryRepository implements NotificationDeliveryRepositoryInterface
{
    public function paginate(int $perPage = 25): LengthAwarePaginator
    {
        return NotificationDelivery::query()->latest('delivered_at')->paginate($perPage);
    }

    public function create(array $attributes): NotificationDelivery
    {
        return NotificationDelivery::create($attributes);
    }
}
