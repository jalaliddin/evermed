<?php

namespace App\Jobs;

use App\Models\TelegramSetting;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTelegramNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        private string $tenantId,
        private string $message
    ) {}

    public function handle(TelegramService $telegram): void
    {
        if (!tenancy()->initialized) {
            tenancy()->initialize($this->tenantId);
            $manuallyInitialized = true;
        }

        $settings = TelegramSetting::first();
        if ($settings && $settings->is_active && $settings->bot_token) {
            $telegram->sendMessage($settings->group_chat_id, $settings->bot_token, $this->message);
        }

        if ($manuallyInitialized ?? false) {
            tenancy()->end();
        }
    }
}
