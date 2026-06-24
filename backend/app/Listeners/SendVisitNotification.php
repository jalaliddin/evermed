<?php

namespace App\Listeners;

use App\Events\VisitRegistered;
use App\Jobs\SendTelegramNotification;

class SendVisitNotification
{
    public function handle(VisitRegistered $event): void
    {
        $visit = $event->visit->load(['patient', 'doctor.user', 'services.service', 'inventory.item']);
        $doctor = $visit->doctor;

        $doctorLine = $doctor
            ? 'Dr. ' . $doctor->user->name . ($doctor->specialization ? " ({$doctor->specialization})" : '')
            : '—';

        $paymentMethod = match ($visit->payment_method) {
            'cash'      => '💵 Naqd',
            'card'      => '💳 Karta',
            'insurance' => '🏥 Sug\'urta',
            default     => ucfirst((string) $visit->payment_method),
        };

        $serviceLines = $visit->services->map(
            fn($vs) => "  • {$vs->service->name}" . ($vs->quantity > 1 ? " ×{$vs->quantity}" : '') . ' — ' . number_format((float) $vs->total, 0, '.', ' ') . " so'm"
        )->implode("\n");

        $inventoryLines = '';
        if ($visit->inventory && $visit->inventory->count() > 0) {
            $lines = $visit->inventory->map(function ($inv) {
                $line = "  • {$inv->item->name} ×{$inv->quantity_used} {$inv->item->unit}";
                if ((float) $inv->total_price > 0) {
                    $line .= ' — ' . number_format((float) $inv->total_price, 0, '.', ' ') . " so'm";
                }
                return $line;
            })->implode("\n");
            $inventoryLines = "\n🧪 <b>Materiallar:</b>\n{$lines}\n";
        }

        $message  = "📋 <b>EverMED — YANGI TASHRIF</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "👤 <b>Bemor:</b> {$visit->patient->full_name}\n";
        if ($visit->patient->phone) {
            $message .= "📞 <b>Tel:</b> {$visit->patient->phone}\n";
        }
        $message .= "👨‍⚕️ <b>Shifokor:</b> {$doctorLine}\n";
        $message .= "📅 <b>Sana:</b> " . $visit->visited_at->format('d.m.Y H:i') . "\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "🩺 <b>Xizmatlar:</b>\n{$serviceLines}\n";
        $message .= $inventoryLines;
        if ((float) $visit->discount > 0) {
            $message .= "🎁 <b>Chegirma:</b> −" . number_format((float) $visit->discount, 0, '.', ' ') . " so'm\n";
        }
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "💰 <b>Jami to'landi:</b> " . number_format((float) ($visit->total_amount - $visit->discount), 0, '.', ' ') . " so'm\n";
        $message .= "💳 <b>To'lov usuli:</b> {$paymentMethod}\n";

        SendTelegramNotification::dispatch(tenant('id'), $message);
    }
}
