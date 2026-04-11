<?php

declare(strict_types=1);

use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

// Route::get('/health', function () {
//     return response()->json(['status' => 'ok']);
// });
Route::apiResource('health', HealthController::class);
