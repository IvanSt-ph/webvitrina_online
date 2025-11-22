<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

// ⬇️ ДОБАВЛЯЕМ эти use — обязательно
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

use App\Models\Product;
use App\Policies\ProductPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Маппинг моделей на политики.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Product::class => ProductPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        /*
        |--------------------------------------------------------------------------
        | 🎨 Кастомное HTML-письмо подтверждения email
        |--------------------------------------------------------------------------
        */
        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('Подтверждение email — WebVitrina')
                ->view('emails.verify-email', [
                    'url'  => $url,
                    'user' => $notifiable,
                ]);
        });
    }
}
