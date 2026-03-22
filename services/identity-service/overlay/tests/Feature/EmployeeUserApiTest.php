<?php

namespace Tests\Feature;

use App\Infrastructure\Messaging\TopicPublisher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Mockery;
use Tests\TestCase;

class EmployeeUserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_employee_and_user_with_public_uuid_ids(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'AdminPass123!',
            'role' => User::ROLE_ADMIN,
        ]);

        Passport::actingAs($admin);

        $publisher = Mockery::mock(TopicPublisher::class);
        $publisher->shouldReceive('publish')->once();
        $this->instance(TopicPublisher::class, $publisher);

        $employeeResponse = $this->postJson(route('employees.store', absolute: false), [
            'employee_code' => 'EMP-1002',
            'full_name' => 'Farez',
            'department' => 'IT Development',
            'job_title' => 'Programmer',
            'email' => 'farez@example.com',
        ]);

        $employeeResponse->assertOk();
        $employeeId = $employeeResponse->json('data.id');

        $this->assertTrue(Str::isUuid($employeeId));

        $userResponse = $this->postJson(route('users.store', absolute: false), [
            'employee_id' => $employeeId,
            'name' => 'Farez User',
            'email' => 'farez.user@example.com',
            'password' => 'FarezPass123!',
            'role' => User::ROLE_STAFF,
        ]);

        $userResponse
            ->assertOk()
            ->assertJsonPath('data.employee.id', $employeeId);

        $this->assertTrue(Str::isUuid($userResponse->json('data.id')));
    }

    public function test_lookup_endpoints_return_human_friendly_not_found_messages_for_unknown_uuids(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'AdminPass123!',
            'role' => User::ROLE_ADMIN,
        ]);

        Passport::actingAs($admin);

        $missingUserId = (string) Str::uuid();
        $missingEmployeeId = (string) Str::uuid();

        $this->getJson(route('users.show', ['user' => $missingUserId], absolute: false))
            ->assertNotFound()
            ->assertExactJson([
                'message' => "No user found with ID '{$missingUserId}'.",
                'error' => 'resource_not_found',
            ]);

        $this->getJson(route('employees.show', ['employee' => $missingEmployeeId], absolute: false))
            ->assertNotFound()
            ->assertExactJson([
                'message' => "No employee found with ID '{$missingEmployeeId}'.",
                'error' => 'resource_not_found',
            ]);
    }
}
