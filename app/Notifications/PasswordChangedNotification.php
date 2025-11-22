<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Ваш пароль был успешно изменён — WebVitrina')
            ->view('emails.password-changed', [
                'user' => $notifiable,
                'time' => now()->format('d.m.Y H:i'),
                'ip'   => request()->ip(),
                'agent' => request()->userAgent(),
            ]);
    }
}
