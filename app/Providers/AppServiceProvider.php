<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

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
        /*
        |--------------------------------------------------------------------------
        | 🔥 Русская транслитерация slug (Убрали дубль slug, фикс категории)
        |--------------------------------------------------------------------------
        |
        | Теперь Str::rusSlug('Одежда') → odezhda
        | Работает в сидерах и при создании категорий.
        |
        */
        Str::macro('rusSlug', function ($string) {

            $map = [
                'а' => 'a',  'б' => 'b',  'в' => 'v',  'г' => 'g',  'д' => 'd',
                'е' => 'e',  'ё' => 'yo', 'ж' => 'zh', 'з' => 'z',  'и' => 'i',
                'й' => 'y',  'к' => 'k',  'л' => 'l',  'м' => 'm',  'н' => 'n',
                'о' => 'o',  'п' => 'p',  'р' => 'r',  'с' => 's',  'т' => 't',
                'у' => 'u',  'ф' => 'f',  'х' => 'h',  'ц' => 'ts', 'ч' => 'ch',
                'ш' => 'sh', 'щ' => 'sch','ъ' => '',   'ы' => 'y',  'ь' => '',
                'э' => 'e',  'ю' => 'yu', 'я' => 'ya'
            ];

            $str = mb_strtolower($string);
            $str = strtr($str, $map);
            $str = preg_replace('/[^a-z0-9]+/u', '-', $str);

            return trim($str, '-');
        });

        /*
        |--------------------------------------------------------------------------
        | 📌 Автоматическая загрузка категорий в меню
        |--------------------------------------------------------------------------
        */
        View::composer('profile.partials.category-menu', function ($view) {

            // Оптимальная загрузка: только корневые и дочерние
            $categories = Category::whereNull('parent_id')
                ->with('children.children')
                ->orderBy('sort_order')
                ->get();

            $view->with('categories', $categories);
        });

        /*
        |--------------------------------------------------------------------------
        | 📌 Подключаем Observer для отзывов
        |--------------------------------------------------------------------------
        */
        Review::observe(ReviewObserver::class);

        /*
        |--------------------------------------------------------------------------
        | 📌 Кастомный ответ после логина
        |--------------------------------------------------------------------------
        */
        $this->app->singleton(LoginResponse::class, CustomLoginResponse::class);
    }
}
