<?php

namespace App\Repositories\Contracts;

use App\Models\DeviceHeartbeat;
use Illuminate\Support\Collection;

interface DeviceHeartbeatRepositoryInterface
{
    public function upsertHeartbeat(string $serialNumber, array $attributes): DeviceHeartbeat;

    public function findInactiveDevices(): Collection;

    public function markInactive(DeviceHeartbeat $heartbeat): DeviceHeartbeat;
}
