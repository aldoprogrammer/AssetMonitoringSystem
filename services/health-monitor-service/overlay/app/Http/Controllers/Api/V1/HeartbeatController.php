<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHeartbeatRequest;
use App\Http\Resources\DeviceHeartbeatResource;
use App\Services\HealthMonitorService;

class HeartbeatController extends Controller
{
    public function __construct(private readonly HealthMonitorService $healthMonitor)
    {
    }

    public function store(StoreHeartbeatRequest $request): DeviceHeartbeatResource
    {
        return DeviceHeartbeatResource::make($this->healthMonitor->ingest($request->validated()));
    }
}
