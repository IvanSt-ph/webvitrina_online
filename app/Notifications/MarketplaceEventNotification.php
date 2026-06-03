<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MarketplaceEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public ?string $body = null,
        public ?string $url = null,
    ) {
        $this->afterCommit();
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->title . ' — WebVitrina')
            ->greeting('Здравствуйте, ' . ($notifiable->name ?? 'пользователь') . '!')
            ->line($this->body ?: $this->title);

        if ($this->url) {
            $message->action('Открыть в WebVitrina', url($this->url));
        }

        return $message
            ->line('Это уведомление отправлено согласно настройкам вашего аккаунта.')
            ->salutation('WebVitrina');
    }
}
