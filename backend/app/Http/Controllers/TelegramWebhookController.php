<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TelegramSetting;
use App\Services\TelegramService;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function __construct(private TelegramService $telegram) {}

    public function handle(Request $request, string $tenantId)
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return response()->json(['ok' => false], 404);
        }

        tenancy()->initialize($tenant);

        try {
            $settings = TelegramSetting::first();
            if (!$settings || !$settings->is_active) {
                return response()->json(['ok' => true]);
            }

            $message = $request->input('message');
            if (!$message) {
                return response()->json(['ok' => true]);
            }

            $chatId = $message['chat']['id'] ?? null;
            $text   = trim($message['text'] ?? '');

            if (!$chatId || !$text) {
                return response()->json(['ok' => true]);
            }

            $reply = match (true) {
                str_starts_with($text, '/report') => $this->reportText($tenantId),
                str_starts_with($text, '/stats')  => $this->statsText($tenantId),
                str_starts_with($text, '/start')  => "👋 EverMED CRM botiga xush kelibsiz!\n\nMavjud buyruqlar:\n/report — Bugungi hisobot\n/stats — Umumiy statistika",
                default => null,
            };

            if ($reply) {
                $this->telegram->sendMessage((string) $chatId, $settings->bot_token, $reply);
            }
        } finally {
            tenancy()->end();
        }

        return response()->json(['ok' => true]);
    }

    private function reportText(string $tenantId): string
    {
        $today   = today()->toDateString();
        $patients = \App\Models\Visit::whereDate('visited_at', $today)->distinct('patient_id')->count('patient_id');
        $appointments = \App\Models\Appointment::whereDate('scheduled_at', $today)->count();
        $revenue = \App\Models\Visit::whereDate('visited_at', $today)->where('is_paid', true)->sum('paid_amount');

        $msg  = "📊 <b>BUGUNGI HISOBOT — " . now()->format('d.m.Y') . "</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━\n";
        $msg .= "👥 Bemorlar: {$patients}\n";
        $msg .= "📅 Qabullar: {$appointments}\n";
        $msg .= "💰 Daromad: " . number_format($revenue, 0, '.', ' ') . " so'm\n";
        return $msg;
    }

    private function statsText(string $tenantId): string
    {
        $total    = \App\Models\Patient::count();
        $doctors  = \App\Models\Doctor::count();
        $today    = \App\Models\Visit::whereDate('visited_at', today())->count();

        $msg  = "📈 <b>UMUMIY STATISTIKA</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━\n";
        $msg .= "👥 Jami bemorlar: {$total}\n";
        $msg .= "👨‍⚕️ Shifokorlar: {$doctors}\n";
        $msg .= "🏥 Bugungi tashriflar: {$today}\n";
        return $msg;
    }
}
