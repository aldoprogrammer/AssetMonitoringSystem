<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function (): void {
        Route::apiResource('users', UserController::class)->only(['index', 'store', 'show', 'update']);
        Route::apiResource('employees', EmployeeController::class)->only(['index', 'store', 'show', 'update']);
    });
});
