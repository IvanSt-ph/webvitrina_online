<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends BaseVerifyEmail
{
    /**
     * Формируем кастомное письмо.
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Подтверждение email — WebVitrina')
            ->view('emails.verify-email', [
                'url'  => $verificationUrl,
                'user' => $notifiable,
            ]);
    }
}
