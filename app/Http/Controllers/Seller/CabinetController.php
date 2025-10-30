<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\ProductStat;
use App\Models\Review;
use Carbon\Carbon;

class CabinetController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 🔹 Получаем ID всех товаров продавца
        $productIds = Product::where('user_id', $user->id)->pluck('id');

        // 🔹 1. Всего товаров (все статусы)
        $totalAllProducts = Product::where('user_id', $user->id)->count();

        // 🔹 2. Опубликовано товаров (active или 1)
        $totalActiveProducts = Product::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('status', 'active')
                  ->orWhere('status', 1);
            })
            ->count();

        // 🔹 3. Просмотры за последние 7 дней
        $views7days = ProductStat::whereIn('product_id', $productIds)
            ->where('date', '>=', Carbon::now()->subDays(7)->toDateString())
            ->sum('views');

        // 🔹 4. Комментарии (только одобренные)
        $commentsCount = Review::whereIn('product_id', $productIds)
            ->where('status', Review::STATUS_APPROVED)
            ->count();

// 🔹 5. Средний рейтинг продавца
$reviews = Review::whereIn('product_id', $productIds)
    ->where('status', Review::STATUS_APPROVED)
    ->pluck('rating');

$avgRatingRaw = $reviews->count() > 0
    ? $reviews->sum() / $reviews->count()
    : 0;
$avgRating = $avgRatingRaw ? round($avgRatingRaw, 2) : 0.00;



        // ⚙️ Формируем массив для карточек
        $stats = [
            [
                'label' => 'Всего товаров',
                'value' => number_format($totalAllProducts, 0, '.', ' '),
                'color' => 'text-gray-600',
            ],
            [
                'label' => 'Опубликовано товаров',
                'value' => number_format($totalActiveProducts, 0, '.', ' '),
                'color' => 'text-indigo-600',
            ],
            [
                'label' => 'Просмотров за 7 дней',
                'value' => number_format($views7days, 0, '.', ' '),
                'color' => 'text-blue-600',
            ],
            [
                'label' => 'Комментариев (отзывов)',
                'value' => number_format($commentsCount, 0, '.', ' '),
                'color' => 'text-green-600',
            ],
[
    'label' => 'Рейтинг продавца',
    'value' => $avgRating > 0 ? number_format($avgRating, 2, '.', '') . ' / 5' : '—',
    'raw'   => (float) $avgRating,
    'color' => 'text-yellow-600',
],

        ];

        return view('seller.cabinet', compact('stats'));
    }
}
