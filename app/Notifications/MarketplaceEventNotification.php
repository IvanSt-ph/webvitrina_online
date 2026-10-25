<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

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
            ->greeting($this->plainText('Здравствуйте, ' . ($notifiable->name ?? 'пользователь') . '!'))
            ->line($this->plainText($this->body ?: $this->title));

        if ($this->url) {
            $message->action('Открыть в WebVitrina', url($this->url));
        }

        return $message
            ->line('Это уведомление отправлено согласно настройкам вашего аккаунта.')
            ->salutation('WebVitrina');
    }

    private function plainText(string $text): HtmlString
    {
        // Entities are decoded as text by CommonMark, never reparsed as Markdown
        // or HTML. Encode whitespace too so input cannot start an indented block.
        return new HtmlString(preg_replace_callback(
            '/[[:punct:]\s]/',
            static fn (array $match) => '&#' . ord($match[0]) . ';',
            $text,
        ));
    }
}
