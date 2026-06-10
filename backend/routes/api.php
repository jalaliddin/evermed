<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Auth (central)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// Super Admin (central)
Route::prefix('admin')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index']);
        Route::apiResource('tenants', TenantController::class);
        Route::apiResource('subscriptions', SubscriptionController::class)->only(['index', 'store', 'update']);
    });
