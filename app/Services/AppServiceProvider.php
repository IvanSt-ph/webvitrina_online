<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // --------------------------------------------
        // 🔗 Автоматическое создание storage:link
        // --------------------------------------------
        $link = public_path('storage');
        $target = storage_path('app/public');

        // Если ссылки нет — создаём
        if (!file_exists($link)) {
            try {
                Artisan::call('storage:link');
            } catch (\Exception $e) {
                // Молча игнорируем ошибки
            }
        }
    }
}
