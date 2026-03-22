<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Services\AuditLogService;
use Mockery;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    public function test_record_uses_routing_key_as_fallback_event_type(): void
    {
        $repository = Mockery::mock(AuditLogRepositoryInterface::class);
        $repository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $payload): bool {
                return $payload['event_type'] === 'health.device_inactive'
                    && $payload['routing_key'] === 'health.device_inactive'
                    && $payload['source_service'] === 'unknown';
            }))
            ->andReturn(new AuditLog());

        $service = new AuditLogService($repository);
        $service->record(['payload' => ['device_serial_number' => 'LAP-1001']], 'health.device_inactive');
    }
}
