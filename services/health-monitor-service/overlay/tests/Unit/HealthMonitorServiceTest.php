<?php

namespace Tests\Unit;

use App\Models\DeviceHeartbeat;
use App\Repositories\Contracts\DeviceHeartbeatRepositoryInterface;
use App\Services\HealthMonitorService;
use App\Infrastructure\Messaging\TopicPublisher;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class HealthMonitorServiceTest extends TestCase
{
    public function test_process_inactive_devices_marks_records_and_publishes_events(): void
    {
        $heartbeat = new DeviceHeartbeat([
            'device_serial_number' => 'LAP-1001',
            'status' => DeviceHeartbeat::STATUS_ONLINE,
            'last_seen_at' => now()->subMinutes(30),
        ]);
        $heartbeat->exists = true;

        $inactiveHeartbeat = tap(clone $heartbeat, function (DeviceHeartbeat $model): void {
            $model->status = DeviceHeartbeat::STATUS_INACTIVE;
        });

        $repository = Mockery::mock(DeviceHeartbeatRepositoryInterface::class);
        $repository->shouldReceive('findInactiveDevices')
            ->once()
            ->andReturn(new Collection([$heartbeat]));
        $repository->shouldReceive('markInactive')
            ->once()
            ->with($heartbeat)
            ->andReturn($inactiveHeartbeat);

        $publisher = Mockery::mock(TopicPublisher::class);
        $publisher->shouldReceive('publish')
            ->once()
            ->with('health.device_inactive', Mockery::on(fn (array $payload): bool => $payload['payload']['device_serial_number'] === 'LAP-1001'));

        $service = new HealthMonitorService($repository, $publisher);

        $this->assertSame(1, $service->processInactiveDevices());
    }
}
