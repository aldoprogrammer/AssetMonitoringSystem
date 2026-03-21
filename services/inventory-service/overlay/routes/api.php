<?php

use App\Http\Controllers\Api\V1\AssetController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::apiResource('assets', AssetController::class)->only(['index', 'store', 'show', 'update']);
    Route::get('assets/serial/{serialNumber}/status', [AssetController::class, 'validateStatus']);
});
