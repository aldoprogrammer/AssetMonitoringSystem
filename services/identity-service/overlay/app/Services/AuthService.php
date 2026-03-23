<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthService
{
    public function attemptLogin(string $email, string $password): array
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are invalid.'],
            ]);
        }

        try {
            $token = $user->createToken('asset-monitoring-system-api');
        } catch (Throwable $exception) {
            error_log(sprintf(
                'Failed to create Passport token for %s: %s in %s:%d',
                $email,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
            ));

            throw $exception;
        }

        return [
            'token_type' => 'Bearer',
            'access_token' => $token->accessToken,
            'user' => $user->load('employee'),
        ];
    }
}
