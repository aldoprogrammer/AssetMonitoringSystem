<?php

namespace App\Repositories\Eloquent;

use App\Models\DeviceHeartbeat;
use App\Repositories\Contracts\DeviceHeartbeatRepositoryInterface;
use Illuminate\Support\Collection;

class DeviceHeartbeatRepository implements DeviceHeartbeatRepositoryInterface
{
    public function upsertHeartbeat(string $serialNumber, array $attributes): DeviceHeartbeat
    {
        return DeviceHeartbeat::query()->updateOrCreate(
            ['device_serial_number' => $serialNumber],
            $attributes,
        );
    }

    public function findInactiveDevices(): Collection
    {
        return DeviceHeartbeat::query()
            ->where('status', '!=', DeviceHeartbeat::STATUS_INACTIVE)
            ->where('last_seen_at', '<', now()->subMinutes((int) config('services.health.device_inactive_after_minutes')))
            ->get();
    }

    public function markInactive(DeviceHeartbeat $heartbeat): DeviceHeartbeat
    {
        $heartbeat->update(['status' => DeviceHeartbeat::STATUS_INACTIVE]);

        return $heartbeat->refresh();
    }
}
