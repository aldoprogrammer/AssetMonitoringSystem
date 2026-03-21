<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $response = $this->authService->attemptLogin(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        return response()->json([
            'token_type' => $response['token_type'],
            'access_token' => $response['access_token'],
            'user' => UserResource::make($response['user']),
        ]);
    }
}
