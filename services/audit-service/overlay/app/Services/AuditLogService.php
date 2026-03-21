<?php

namespace App\Services;

use App\Repositories\Contracts\AuditLogRepositoryInterface;

class AuditLogService
{
    public function __construct(private readonly AuditLogRepositoryInterface $auditLogs)
    {
    }

    public function paginate(int $perPage = 25)
    {
        return $this->auditLogs->paginate($perPage);
    }

    public function record(array $event, string $routingKey): void
    {
        $this->auditLogs->create([
            'event_type' => $event['event_type'] ?? $routingKey,
            'routing_key' => $routingKey,
            'source_service' => $event['source_service'] ?? 'unknown',
            'payload' => $event['payload'] ?? [],
            'occurred_at' => $event['occurred_at'] ?? now(),
        ]);
    }
}
