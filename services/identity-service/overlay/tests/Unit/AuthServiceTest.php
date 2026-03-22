<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_attempt_login_throws_validation_exception_for_invalid_credentials(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'CorrectPass123!',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->expectException(ValidationException::class);

        app(AuthService::class)->attemptLogin('admin@example.com', 'WrongPass123!');
    }
}
