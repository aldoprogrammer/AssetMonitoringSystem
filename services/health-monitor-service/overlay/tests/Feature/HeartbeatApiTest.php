<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeartbeatApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_heartbeat_endpoint_upserts_by_device_serial_number(): void
    {
        $this->postJson(route('heartbeats.store', absolute: false), [
            'device_serial_number' => 'LAP-1001',
            'metadata' => ['battery' => 91],
        ])->assertOk();

        $this->postJson(route('heartbeats.store', absolute: false), [
            'device_serial_number' => 'LAP-1001',
            'metadata' => ['battery' => 75],
        ])->assertOk();

        $this->assertDatabaseCount('device_heartbeats', 1);
        $this->assertDatabaseHas('device_heartbeats', [
            'device_serial_number' => 'LAP-1001',
            'status' => 'online',
        ]);
    }
}
