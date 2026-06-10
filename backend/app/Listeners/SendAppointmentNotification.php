<?php

namespace App\Listeners;

use App\Events\AppointmentStatusChanged;
use App\Jobs\SendTelegramNotification;

class SendAppointmentNotification
{
    public function handle(AppointmentStatusChanged $event): void
    {
        $appointment = $event->appointment->load(['patient', 'doctor.user']);
        $status = match($event->appointment->status) {
            'confirmed' => '✅ QABUL TASDIQLANDI',
            'cancelled' => '❌ QABUL BEKOR QILINDI',
            'in_progress' => '🏥 BEMOR QABUL QILINDI',
            'completed' => '✅ QABUL YAKUNLANDI',
            default => null,
        };

        if (!$status) return;

        $message = "<b>{$status}</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━\n";
        $message .= "Bemor: {$appointment->patient->full_name}\n";
        $message .= "Shifokor: Dr. {$appointment->doctor->user->name}\n";
        $message .= "Vaqt: " . $appointment->scheduled_at->format('d.m.Y H:i') . "\n";

        SendTelegramNotification::dispatch(tenant('id'), $message);
    }
}
