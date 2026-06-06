<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdSlot extends Model
{
    public const HOME_FEATURED_PRODUCTS = 'home_featured_products';
    public const HOME_WEEKLY_SHOPS = 'home_weekly_shops';
    public const CATEGORY_FEATURED_PRODUCTS = 'category_featured_products';
    public const PRODUCT_RELATED_PROMOTED = 'product_related_promoted';

    protected $fillable = [
        'key',
        'name',
        'placement',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function labels(): array
    {
        return [
            self::HOME_FEATURED_PRODUCTS => 'Рекомендуемые товары',
            self::HOME_WEEKLY_SHOPS => 'Магазины недели',
            self::CATEGORY_FEATURED_PRODUCTS => 'Популярное в категории',
            self::PRODUCT_RELATED_PROMOTED => 'Партнёрский блок в карточке товара',
        ];
    }

    public static function publicGuide(): array
    {
        return [
            self::HOME_FEATURED_PRODUCTS => [
                'label' => 'Рекомендуемые товары',
                'placement' => 'Главная страница',
                'where' => 'На главной, ниже большого баннера и выше обычного каталога.',
                'target' => 'Лучше выбирать товар. Своя ссылка тоже возможна.',
                'url' => route('home'),
                'enabled' => true,
            ],
            self::HOME_WEEKLY_SHOPS => [
                'label' => 'Магазины недели',
                'placement' => 'Главная страница',
                'where' => 'На главной, отдельным партнёрским блоком над каталогом.',
                'target' => 'Лучше выбирать магазин. Своя ссылка тоже возможна.',
                'url' => route('home'),
                'enabled' => true,
            ],
            self::CATEGORY_FEATURED_PRODUCTS => [
                'label' => 'Популярное в категории',
                'placement' => 'Страницы категорий',
                'where' => 'На странице категории, над сеткой товаров.',
                'target' => 'Лучше выбирать товар. Блок общий для категорий, без привязки к конкретной категории.',
                'url' => route('category.index'),
                'enabled' => true,
            ],
            self::PRODUCT_RELATED_PROMOTED => [
                'label' => 'Партнёрский блок в карточке товара',
                'placement' => 'Страница товара',
                'where' => 'Слот подготовлен в базе, публичный вывод пока не подключён.',
                'target' => 'Будет лучше работать с товарами или магазинами после отдельной аккуратной встройки в карточку товара.',
                'url' => null,
                'enabled' => false,
            ],
        ];
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class);
    }
}
