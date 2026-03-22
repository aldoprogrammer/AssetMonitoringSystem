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
}
