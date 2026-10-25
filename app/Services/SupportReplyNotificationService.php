<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SupportReplyNotificationService
{
    public function send(Message $message): void
    {
        $conversation = $message->conversation;
        if (! $conversation?->isSupport() || $message->sender?->role !== 'admin'
            || $message->type === Message::TYPE_INTERNAL_NOTE) {
            return;
        }
        DB::transaction(function () use ($message, $conversation): void {
            // Same User-before-Message lock order as account deletion.
            $user = User::whereKey($conversation->buyer_id)->lockForUpdate()->first();
            $current = Message::whereKey($message->id)->lockForUpdate()->first();
            if (! $user || ! $current || $current->support_notified_at !== null) {
                return;
            }
            app(UserNotificationService::class)->create($user, 'support_message', 'Ответ поддержки',
                'Поддержка ответила в вашем обращении.', route('chats.show', $conversation, false),
                ['conversation_id' => $conversation->id, 'message_id' => $message->id]);
            $current->forceFill(['support_notified_at' => now()])->save();
        });
    }
}
