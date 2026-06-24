<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

// Telegram webhook (public, called by Telegram servers)
Route::post('webhook/telegram/{tenantId}', [TelegramWebhookController::class, 'handle']);

// Auth (central)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    // InitializeTenancyOptionally must run before auth:sanctum so tenant user
    // tokens (stored in tenant DB) are found via the correct connection.
    Route::middleware([\App\Http\Middleware\InitializeTenancyOptionally::class, 'auth:sanctum'])->group(function () {
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
        Route::post('tenants/{id}/reset-password', [TenantController::class, 'resetAdminPassword']);
        Route::apiResource('subscriptions', SubscriptionController::class)->only(['index', 'store', 'update']);
    });
