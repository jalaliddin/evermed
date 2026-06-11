<?php

namespace App\Listeners;

use App\Events\AppointmentStatusChanged;
use App\Jobs\SendTelegramNotification;

class SendAppointmentNotification
{
    public function handle(AppointmentStatusChanged $event): void
    {
        // Only notify for confirmed/cancelled — payment notification covers in_progress/completed
        $status = match($event->appointment->status) {
            'confirmed' => '✅ QABUL TASDIQLANDI',
            'cancelled' => '❌ QABUL BEKOR QILINDI',
            default     => null,
        };

        if (!$status) return;

        $appointment = $event->appointment->load(['patient', 'doctor.user']);

        $doctorLine = 'Dr. ' . $appointment->doctor->user->name;
        if ($appointment->doctor->specialization) {
            $doctorLine .= " ({$appointment->doctor->specialization})";
        }

        $message = "<b>{$status}</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━\n";
        $message .= "Bemor: {$appointment->patient->full_name}\n";
        $message .= "Shifokor: {$doctorLine}\n";
        $message .= "Vaqt: " . $appointment->scheduled_at->format('d.m.Y H:i') . "\n";

        SendTelegramNotification::dispatch(tenant('id'), $message);
    }
}
