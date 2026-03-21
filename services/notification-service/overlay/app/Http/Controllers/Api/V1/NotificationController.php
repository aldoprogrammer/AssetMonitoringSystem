<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationDeliveryResource;
use App\Services\NotificationService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return NotificationDeliveryResource::collection($this->notifications->paginate());
    }
}
