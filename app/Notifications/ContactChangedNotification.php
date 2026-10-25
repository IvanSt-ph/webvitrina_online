<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $field)
    {
        $this->afterCommit();
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject('Изменены контактные данные — WebVitrina')
            ->line($this->field === 'email' ? 'В вашем аккаунте изменён email.' : 'В вашем аккаунте изменён телефон.')
            ->line('Если это сделали не вы, обратитесь в поддержку.');
    }
}
