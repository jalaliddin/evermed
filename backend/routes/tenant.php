<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\AppointmentController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\DoctorController;
use App\Http\Controllers\Tenant\InventoryController;
use App\Http\Controllers\Tenant\NotificationController;
use App\Http\Controllers\Tenant\PatientController;
use App\Http\Controllers\Tenant\ReceiptController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\ServiceController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\VisitController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;


Route::middleware([
    'api',
    InitializeTenancyByRequestData::class,
])->prefix('api/tenant')->group(function () {

    // ── Public (no auth required) — browser window.open() calls ───────────────
    Route::get('visits/{visit}/receipt-preview', [ReceiptController::class, 'preview']);

    Route::middleware('auth:sanctum')->group(function () {

        // ── Shared (admin + receptionist) ──────────────────────────────────────

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);

        // Patients — full CRUD for both roles
        Route::get('patients/{patient}/visits', [PatientController::class, 'visits']);
        Route::get('patients/{patient}/stats', [PatientController::class, 'stats']);
        Route::apiResource('patients', PatientController::class);

        // Doctors — read-only for receptionist (to select doctor in POS)
        Route::get('doctors', [DoctorController::class, 'index']);
        Route::get('doctors/{doctor}', [DoctorController::class, 'show']);

        // Services — read-only for receptionist (to select services in POS)
        Route::get('service-categories', [ServiceController::class, 'categories']);
        Route::get('services', [ServiceController::class, 'index']);

        // Appointments — both roles
        Route::get('appointments/available-slots', [AppointmentController::class, 'availableSlots']);
        Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
        Route::apiResource('appointments', AppointmentController::class);

        // Visits & POS — both roles
        Route::post('visits/{visit}/pay', [VisitController::class, 'pay']);
        Route::post('visits/{visit}/print-receipt', [ReceiptController::class, 'print']);
        Route::apiResource('visits', VisitController::class);

        // Inventory list & stock-out for POS usage — both roles
        Route::get('inventory', [InventoryController::class, 'index']);
        Route::get('inventory/low-stock', [InventoryController::class, 'lowStock']);
        Route::post('inventory/{inventory}/stock-out', [InventoryController::class, 'stockOut']);

        // Notifications — both roles
        Route::patch('notifications/read-all', [NotificationController::class, 'readAll']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::get('notifications', [NotificationController::class, 'index']);

        // ── Admin only ─────────────────────────────────────────────────────────

        Route::middleware('role:admin')->group(function () {

            // Doctors — full CRUD
            Route::get('doctors/{doctor}/report', [DoctorController::class, 'report']);
            Route::get('doctors/{doctor}/appointments', [DoctorController::class, 'appointments']);
            Route::post('doctors', [DoctorController::class, 'store']);
            Route::put('doctors/{doctor}', [DoctorController::class, 'update']);
            Route::delete('doctors/{doctor}', [DoctorController::class, 'destroy']);

            // Services — full CRUD
            Route::post('service-categories', [ServiceController::class, 'storeCategory']);
            Route::put('service-categories/{serviceCategory}', [ServiceController::class, 'updateCategory']);
            Route::delete('service-categories/{serviceCategory}', [ServiceController::class, 'destroyCategory']);
            Route::post('services', [ServiceController::class, 'store']);
            Route::put('services/{service}', [ServiceController::class, 'update']);
            Route::delete('services/{service}', [ServiceController::class, 'destroy']);

            // Inventory — full management + stock-in
            Route::get('inventory/transactions', [InventoryController::class, 'transactions']);
            Route::post('inventory/{inventory}/stock-in', [InventoryController::class, 'stockIn']);
            Route::post('inventory', [InventoryController::class, 'store']);
            Route::put('inventory/{inventory}', [InventoryController::class, 'update']);

            // Reports
            Route::prefix('reports')->group(function () {
                Route::get('financial', [ReportController::class, 'financial']);
                Route::get('doctors', [ReportController::class, 'doctors']);
                Route::get('services', [ReportController::class, 'services']);
                Route::get('inventory', [ReportController::class, 'inventory']);
                Route::get('export', [ReportController::class, 'export']);
            });

            // Settings
            Route::prefix('settings')->group(function () {
                Route::get('clinic', [SettingsController::class, 'clinic']);
                Route::put('clinic', [SettingsController::class, 'updateClinic']);
                Route::post('clinic/logo', [SettingsController::class, 'uploadLogo']);
                Route::get('telegram', [SettingsController::class, 'telegram']);
                Route::put('telegram', [SettingsController::class, 'updateTelegram']);
                Route::post('telegram/test', [SettingsController::class, 'testTelegram']);
                Route::post('telegram/set-webhook', [SettingsController::class, 'setWebhook']);
                Route::get('telegram/webhook-url', [SettingsController::class, 'webhookUrl']);
                Route::get('printer', [SettingsController::class, 'printer']);
                Route::put('printer', [SettingsController::class, 'updatePrinter']);
                Route::get('users', [SettingsController::class, 'users']);
                Route::post('users', [SettingsController::class, 'storeUser']);
                Route::put('users/{user}', [SettingsController::class, 'updateUser']);
                Route::delete('users/{user}', [SettingsController::class, 'destroyUser']);
            });
        });
    });
});
