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
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'api',
    InitializeTenancyByRequestData::class,
])->prefix('api/tenant')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {
        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);

        // Patients
        Route::get('patients/{patient}/visits', [PatientController::class, 'visits']);
        Route::get('patients/{patient}/stats', [PatientController::class, 'stats']);
        Route::apiResource('patients', PatientController::class);

        // Doctors
        Route::get('doctors/{doctor}/report', [DoctorController::class, 'report']);
        Route::get('doctors/{doctor}/appointments', [DoctorController::class, 'appointments']);
        Route::apiResource('doctors', DoctorController::class);

        // Services & Categories
        Route::get('service-categories', [ServiceController::class, 'categories']);
        Route::post('service-categories', [ServiceController::class, 'storeCategory']);
        Route::put('service-categories/{serviceCategory}', [ServiceController::class, 'updateCategory']);
        Route::delete('service-categories/{serviceCategory}', [ServiceController::class, 'destroyCategory']);
        Route::apiResource('services', ServiceController::class)->except(['show']);

        // Appointments
        Route::get('appointments/available-slots', [AppointmentController::class, 'availableSlots']);
        Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
        Route::apiResource('appointments', AppointmentController::class);

        // Visits
        Route::post('visits/{visit}/pay', [VisitController::class, 'pay']);
        Route::post('visits/{visit}/print-receipt', [ReceiptController::class, 'print']);
        Route::get('visits/{visit}/receipt-preview', [ReceiptController::class, 'preview']);
        Route::apiResource('visits', VisitController::class);

        // Inventory
        Route::get('inventory/transactions', [InventoryController::class, 'transactions']);
        Route::get('inventory/low-stock', [InventoryController::class, 'lowStock']);
        Route::post('inventory/{inventory}/stock-in', [InventoryController::class, 'stockIn']);
        Route::post('inventory/{inventory}/stock-out', [InventoryController::class, 'stockOut']);
        Route::apiResource('inventory', InventoryController::class)->except(['show', 'destroy']);

        // Reports
        Route::prefix('reports')->group(function () {
            Route::get('financial', [ReportController::class, 'financial']);
            Route::get('doctors', [ReportController::class, 'doctors']);
            Route::get('services', [ReportController::class, 'services']);
            Route::get('inventory', [ReportController::class, 'inventory']);
            Route::get('export', [ReportController::class, 'export']);
        });

        // Notifications
        Route::patch('notifications/read-all', [NotificationController::class, 'readAll']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::get('notifications', [NotificationController::class, 'index']);

        // Settings
        Route::prefix('settings')->group(function () {
            Route::get('clinic', [SettingsController::class, 'clinic']);
            Route::put('clinic', [SettingsController::class, 'updateClinic']);
            Route::get('telegram', [SettingsController::class, 'telegram']);
            Route::put('telegram', [SettingsController::class, 'updateTelegram']);
            Route::post('telegram/test', [SettingsController::class, 'testTelegram']);
            Route::get('printer', [SettingsController::class, 'printer']);
            Route::put('printer', [SettingsController::class, 'updatePrinter']);
            Route::get('users', [SettingsController::class, 'users']);
            Route::post('users', [SettingsController::class, 'storeUser']);
            Route::put('users/{user}', [SettingsController::class, 'updateUser']);
            Route::delete('users/{user}', [SettingsController::class, 'destroyUser']);
        });
    });
});
