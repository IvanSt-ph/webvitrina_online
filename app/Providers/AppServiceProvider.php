<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Category;
use App\Models\Review;
use App\Observers\ReviewObserver;

use Laravel\Fortify\Contracts\LoginResponse;
use App\Http\Responses\LoginResponse as CustomLoginResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 📌 Автоматическая загрузка категорий в меню
        View::composer('profile.partials.category-menu', function ($view) {
            $categories = Category::whereNull('parent_id')
                ->with('children.children')
                ->get();

            $view->with('categories', $categories);
        });

        // 📌 Подключаем Observer для отзывов
        Review::observe(ReviewObserver::class);

        // 📌 Подключаем кастомный LoginResponse
        $this->app->singleton(LoginResponse::class, CustomLoginResponse::class);
    }
}
