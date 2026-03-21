<?php

use App\Http\Controllers\Api\V1\AssignmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('', [AssignmentController::class, 'index']);
    Route::post('checkout', [AssignmentController::class, 'checkout']);
    Route::post('{assignment}/checkin', [AssignmentController::class, 'checkin']);
});
