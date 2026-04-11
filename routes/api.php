<?php

declare(strict_types=1);

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\OrgController;
use Illuminate\Support\Facades\Route;

// Route::get('/health', function () {
//     return response()->json(['status' => 'ok']);
// });
Route::apiResource('health', HealthController::class);

Route::post('orgs', [OrgController::class, 'store'])->name('orgs.store');
Route::get('orgs/{org}', [OrgController::class, 'show'])->name('orgs.show');
