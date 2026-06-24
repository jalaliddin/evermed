<?php

namespace App\Providers;

use App\Events\AppointmentStatusChanged;
use App\Events\PaymentReceived;
use App\Events\VisitRegistered;
use App\Listeners\SendAppointmentNotification;
use App\Listeners\SendPaymentNotification;
use App\Listeners\SendVisitNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Services\ReceiptService::class);
        $this->app->bind(\App\Services\TelegramService::class);
    }

    public function boot(): void
    {
        Event::listen(AppointmentStatusChanged::class, SendAppointmentNotification::class);
        Event::listen(PaymentReceived::class, SendPaymentNotification::class);
        Event::listen(VisitRegistered::class, SendVisitNotification::class);
    }
}
