<?php

namespace App\Services;

use App\Infrastructure\Messaging\TopicPublisher;
use App\Models\DeviceHeartbeat;
use App\Repositories\Contracts\DeviceHeartbeatRepositoryInterface;

class HealthMonitorService
{
    public function __construct(
        private readonly DeviceHeartbeatRepositoryInterface $heartbeats,
        private readonly TopicPublisher $publisher,
    ) {
    }

    public function ingest(array $payload): DeviceHeartbeat
    {
        return $this->heartbeats->upsertHeartbeat($payload['device_serial_number'], [
            'status' => DeviceHeartbeat::STATUS_ONLINE,
            'metadata' => $payload['metadata'] ?? [],
            'last_seen_at' => now(),
        ]);
    }

    public function processInactiveDevices(): int
    {
        $processed = 0;

        foreach ($this->heartbeats->findInactiveDevices() as $heartbeat) {
            $heartbeat = $this->heartbeats->markInactive($heartbeat);
            $this->publisher->publish('health.device_inactive', [
                'message_id' => (string) str()->uuid(),
                'event_type' => 'health.device_inactive',
                'occurred_at' => now()->toIso8601String(),
                'source_service' => 'health-monitor-service',
                'payload' => [
                    'device_serial_number' => $heartbeat->device_serial_number,
                    'last_seen_at' => $heartbeat->last_seen_at?->toIso8601String(),
                    'status' => $heartbeat->status,
                ],
            ]);
            $processed++;
        }

        return $processed;
    }
}
