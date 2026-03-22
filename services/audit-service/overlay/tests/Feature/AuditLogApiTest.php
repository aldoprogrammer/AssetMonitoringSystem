<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logs_endpoint_returns_paginated_records(): void
    {
        AuditLog::create([
            'event_type' => 'assignment.checked_out',
            'routing_key' => 'assignment.checked_out',
            'source_service' => 'assignment-service',
            'payload' => ['asset_serial_number' => 'LAP-1001'],
            'occurred_at' => now(),
        ]);

        $this->getJson(route('audit-logs.index', absolute: false))
            ->assertOk()
            ->assertJsonPath('data.0.event_type', 'assignment.checked_out');
    }
}
