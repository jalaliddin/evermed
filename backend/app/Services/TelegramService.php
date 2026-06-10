<?php

namespace App\Services;

use GuzzleHttp\Client;

class TelegramService
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client(['timeout' => 10]);
    }

    public function sendMessage(string $chatId, string $token, string $text): bool
    {
        try {
            $response = $this->http->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                ],
            ]);
            return json_decode($response->getBody(), true)['ok'] ?? false;
        } catch (\Exception $e) {
            \Log::error('Telegram send error: ' . $e->getMessage());
            return false;
        }
    }

    public function testConnection(string $token, string $chatId): array
    {
        try {
            $botInfo = $this->http->get("https://api.telegram.org/bot{$token}/getMe");
            $bot = json_decode($botInfo->getBody(), true);

            if (!($bot['ok'] ?? false)) {
                return ['success' => false, 'message' => 'Bot token noto\'g\'ri'];
            }

            $this->sendMessage($chatId, $token, "✅ EverMED CRM ulanishi tekshirildi!\nBot: @{$bot['result']['username']}");

            return ['success' => true, 'message' => 'Test xabar yuborildi', 'bot' => $bot['result']];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function sendDailyReport(\App\Models\Tenant $tenant): void
    {
        tenancy()->initialize($tenant);

        $settings = \App\Models\TelegramSetting::first();
        if (!$settings || !$settings->is_active || !$settings->bot_token) {
            tenancy()->end();
            return;
        }

        $today = today()->toDateString();
        $patients = \App\Models\Visit::whereDate('visited_at', $today)->distinct('patient_id')->count('patient_id');
        $appointments = \App\Models\Appointment::whereDate('scheduled_at', $today)->count();
        $cancelled = \App\Models\Appointment::whereDate('scheduled_at', $today)->where('status', 'cancelled')->count();
        $revenue = \App\Models\Visit::whereDate('visited_at', $today)->where('is_paid', true)->sum('paid_amount');

        $topDoctor = \App\Models\Doctor::with('user')
            ->withCount(['visits as visit_count' => fn($q) => $q->whereDate('visited_at', $today)])
            ->orderByDesc('visit_count')
            ->first();

        $message = "📊 <b>KUNLIK HISOBOT — " . now()->format('d.m.Y') . "</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━\n";
        $message .= "👥 Bemorlar: {$patients}\n";
        $message .= "📅 Qabullar: {$appointments}" . ($cancelled > 0 ? " ({$cancelled} bekor)" : '') . "\n";
        $message .= "💰 Daromad: " . number_format($revenue, 0, '.', ' ') . " so'm\n";
        if ($topDoctor && $topDoctor->visit_count > 0) {
            $message .= "🏆 Top shifokor: Dr. {$topDoctor->user->name} ({$topDoctor->visit_count} bemor)\n";
        }

        $this->sendMessage($settings->group_chat_id, $settings->bot_token, $message);

        tenancy()->end();
    }
}
