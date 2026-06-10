<?php

use App\Models\Tenant;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('telegram:daily-report', function () {
    $telegram = new TelegramService();
    $tenants = Tenant::where('status', 'active')->get();

    foreach ($tenants as $tenant) {
        $telegram->sendDailyReport($tenant);
    }

    $this->info('Daily reports sent to ' . $tenants->count() . ' tenants');
})->purpose('Send daily reports to all active tenants via Telegram');

Schedule::command('telegram:daily-report')->dailyAt('18:00');
