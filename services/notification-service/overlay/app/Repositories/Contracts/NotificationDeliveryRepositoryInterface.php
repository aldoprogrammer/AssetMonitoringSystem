<?php

namespace App\Repositories\Contracts;

use App\Models\NotificationDelivery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NotificationDeliveryRepositoryInterface
{
    public function paginate(int $perPage = 25): LengthAwarePaginator;

    public function create(array $attributes): NotificationDelivery;
}
