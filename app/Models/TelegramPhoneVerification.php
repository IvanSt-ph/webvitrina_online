<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramPhoneVerification extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['phone', 'token_hash', 'session_hash', 'telegram_user_id', 'active_telegram_user_id'];

    protected function casts(): array
    {
        return array_fill_keys(['bound_at', 'contact_verified_at', 'expires_at', 'consumed_at', 'invalidated_at'], 'datetime');
    }

    public function pending(): bool
    {
        return ! $this->consumed_at && ! $this->invalidated_at && $this->expires_at->isFuture();
    }
}
