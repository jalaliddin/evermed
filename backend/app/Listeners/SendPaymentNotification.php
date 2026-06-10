<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Jobs\SendTelegramNotification;

class SendPaymentNotification
{
    public function handle(PaymentReceived $event): void
    {
        $visit = $event->visit->load(['patient', 'doctor.user', 'services.service']);

        $services = $visit->services->map(fn($vs) => $vs->service->name)->implode(', ');

        $message = "<b>💰 TO'LOV QABUL QILINDI</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━\n";
        $message .= "Bemor: {$visit->patient->full_name}\n";
        $message .= "Xizmatlar: {$services}\n";
        $message .= "Summa: " . number_format($visit->paid_amount, 0, '.', ' ') . " so'm\n";
        $message .= "Usul: " . ucfirst($visit->payment_method) . "\n";

        SendTelegramNotification::dispatch(tenant('id'), $message);
    }
}
