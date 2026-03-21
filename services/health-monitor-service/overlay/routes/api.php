<?php

use App\Http\Controllers\Api\V1\HeartbeatController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('heartbeats', [HeartbeatController::class, 'store']);
});
