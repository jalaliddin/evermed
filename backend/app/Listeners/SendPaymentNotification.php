<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Jobs\SendTelegramNotification;

class SendPaymentNotification
{
    public function handle(PaymentReceived $event): void
    {
        $visit  = $event->visit->load(['patient', 'doctor.user', 'services.service']);
        $doctor = $visit->doctor;

        $doctorLine = $doctor
            ? 'Dr. ' . $doctor->user->name . ($doctor->specialization ? " ({$doctor->specialization})" : '')
            : '—';

        $paymentMethod = match ($visit->payment_method) {
            'cash'      => '💵 Naqd',
            'card'      => '💳 Karta',
            'insurance' => '🏥 Sug\'urta',
            default     => ucfirst($visit->payment_method),
        };

        $serviceLines = $visit->services->map(
            fn($vs) => "  • {$vs->service->name}" . ($vs->quantity > 1 ? " ×{$vs->quantity}" : '') . ' — ' . number_format($vs->total, 0, '.', ' ') . " so'm"
        )->implode("\n");

        $message  = "🏥 <b>EverMED — TO'LOV QABUL QILINDI</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "👤 <b>Bemor:</b> {$visit->patient->full_name}\n";
        if ($visit->patient->phone) {
            $message .= "📞 <b>Tel:</b> {$visit->patient->phone}\n";
        }
        $message .= "👨‍⚕️ <b>Shifokor:</b> {$doctorLine}\n";
        $message .= "📅 <b>Sana:</b> " . $visit->visited_at->format('d.m.Y H:i') . "\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "🩺 <b>Xizmatlar:</b>\n{$serviceLines}\n";
        if ($visit->discount > 0) {
            $message .= "🎁 <b>Chegirma:</b> −" . number_format($visit->discount, 0, '.', ' ') . " so'm\n";
        }
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "💰 <b>Jami to'landi:</b> " . number_format($visit->paid_amount, 0, '.', ' ') . " so'm\n";
        $message .= "💳 <b>To'lov usuli:</b> {$paymentMethod}\n";

        SendTelegramNotification::dispatch(tenant('id'), $message);
    }
}
