<?php

namespace Tests\Feature;

use App\Services\UserSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserSyncIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_identity_user_event_is_projected_into_assignment_service(): void
    {
        $payload = [
            'id' => '1717cc07-65f8-4b03-b2a0-8929c508ed0a',
            'employee_id' => '97dcb0f5-8f1c-43e9-9b37-f0b9b5bc9e12',
            'name' => 'Ava Admin',
            'email' => 'ava.admin@assetmonitoringsystem.local',
            'role' => 'admin',
        ];

        app(UserSyncService::class)->sync($payload);

        $this->assertDatabaseHas('user_projections', [
            'external_user_id' => '1717cc07-65f8-4b03-b2a0-8929c508ed0a',
            'employee_id' => '97dcb0f5-8f1c-43e9-9b37-f0b9b5bc9e12',
            'email' => 'ava.admin@assetmonitoringsystem.local',
            'role' => 'admin',
        ]);
    }

    public function test_checkin_returns_human_friendly_not_found_message_for_unknown_uuid(): void
    {
        $missingAssignmentId = (string) Str::uuid();

        $this->postJson(route('assignments.checkin', ['assignment' => $missingAssignmentId], absolute: false))
            ->assertNotFound()
            ->assertExactJson([
                'message' => "No assignment found with ID '{$missingAssignmentId}'.",
                'error' => 'resource_not_found',
            ]);
    }
}
