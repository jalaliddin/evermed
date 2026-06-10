<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramSetting extends Model
{
    protected $table = 'telegram_settings';
    protected $fillable = ['bot_token', 'group_chat_id', 'notifications', 'is_active'];

    protected function casts(): array
    {
        return [
            'notifications' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
