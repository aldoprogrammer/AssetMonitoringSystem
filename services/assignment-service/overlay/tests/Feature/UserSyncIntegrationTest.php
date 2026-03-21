<?php

namespace Tests\Feature;

use App\Services\UserSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSyncIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_identity_user_event_is_projected_into_assignment_service(): void
    {
        $payload = [
            'id' => 101,
            'employee_id' => 9001,
            'name' => 'Ava Admin',
            'email' => 'ava.admin@asset_monitoring_system.local',
            'role' => 'admin',
        ];

        app(UserSyncService::class)->sync($payload);

        $this->assertDatabaseHas('user_projections', [
            'external_user_id' => 101,
            'employee_id' => 9001,
            'email' => 'ava.admin@asset_monitoring_system.local',
            'role' => 'admin',
        ]);
    }
}
