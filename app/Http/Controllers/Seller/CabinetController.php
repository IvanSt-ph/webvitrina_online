<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStat;
use App\Models\Review;
use App\Models\SellerPlanRequest;
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

        $followersCount = $user->shop?->followers()->count() ?? 0;
        $pendingOrdersCount = Order::where('seller_id', $user->id)
            ->where('status', Order::STATUS_PENDING)
            ->count();
        $cancellationRequestsCount = Order::where('seller_id', $user->id)
            ->whereNotNull('cancellation_requested_at')
            ->where('status', '!=', Order::STATUS_CANCELED)
            ->count();
        $unreadMessagesCount = Conversation::where(function ($query) use ($user) {
                $query->where('buyer_id', $user->id)
                    ->orWhere('seller_id', $user->id);
            })
            ->whereHas('messages', fn ($query) => $query
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_at'))
            ->count();
        $outOfStockProductsCount = Product::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('stock', '<=', 0)
            ->count();
        $lowStockProductsCount = Product::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereBetween('stock', [1, 3])
            ->count();
        $draftProductsCount = Product::where('user_id', $user->id)
            ->where('status', 'draft')
            ->count();
        $pendingPlanRequest = SellerPlanRequest::where('user_id', $user->id)
            ->where('status', SellerPlanRequest::STATUS_PENDING)
            ->latest()
            ->first();
        $actionOrders = Order::where('seller_id', $user->id)
            ->with(['user', 'items.product'])
            ->where(function ($query) {
                $query->where('status', Order::STATUS_PENDING)
                    ->orWhereNotNull('cancellation_requested_at');
            })
            ->where('status', '!=', Order::STATUS_CANCELED)
            ->latest()
            ->limit(5)
            ->get();

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
                'color' => 'text-slate-900',
            ],
            [
                'label' => 'Опубликовано товаров',
                'value' => number_format($totalActiveProducts, 0, '.', ' '),
                'color' => 'text-indigo-600',
            ],
            [
                'label' => 'Просмотров за 7 дней',
                'value' => number_format($views7days, 0, '.', ' '),
                'color' => 'text-indigo-600',
            ],
            [
                'label' => 'Комментариев (отзывов)',
                'value' => number_format($commentsCount, 0, '.', ' '),
                'color' => 'text-slate-900',
            ],
            [
                'label' => 'Подписчиков',
                'value' => number_format($followersCount, 0, '.', ' '),
                'color' => 'text-indigo-600',
            ],
[
    'label' => 'Рейтинг продавца',
    'value' => $avgRating > 0 ? number_format($avgRating, 2, '.', '') . ' / 5' : '—',
    'raw'   => (float) $avgRating,
    'color' => 'text-indigo-600',
],

        ];

        $actionCards = [
            [
                'label' => 'Новые заказы',
                'value' => $pendingOrdersCount,
                'text' => 'Нужно принять или уточнить',
                'href' => route('seller.orders.index', ['status' => Order::STATUS_PENDING]),
                'icon' => 'ri-shopping-bag-3-line',
                'tone' => $pendingOrdersCount > 0 ? 'amber' : 'slate',
            ],
            [
                'label' => 'Запросы отмены',
                'value' => $cancellationRequestsCount,
                'text' => 'Покупатели ждут решения',
                'href' => route('seller.orders.index', ['action' => 'cancel_request']),
                'icon' => 'ri-close-circle-line',
                'tone' => $cancellationRequestsCount > 0 ? 'rose' : 'slate',
            ],
            [
                'label' => 'Непрочитанные чаты',
                'value' => $unreadMessagesCount,
                'text' => 'Ответы покупателям и поддержке',
                'href' => route('chats.index'),
                'icon' => 'ri-chat-3-line',
                'tone' => $unreadMessagesCount > 0 ? 'indigo' : 'slate',
            ],
            [
                'label' => 'Нет в наличии',
                'value' => $outOfStockProductsCount,
                'text' => 'Товары нельзя купить',
                'href' => route('seller.products.index', ['stock' => 'out']),
                'icon' => 'ri-alert-line',
                'tone' => $outOfStockProductsCount > 0 ? 'rose' : 'emerald',
            ],
            [
                'label' => 'Мало остатков',
                'value' => $lowStockProductsCount,
                'text' => 'Пора пополнить склад',
                'href' => route('seller.products.index', ['stock' => 'low']),
                'icon' => 'ri-inbox-archive-line',
                'tone' => $lowStockProductsCount > 0 ? 'amber' : 'slate',
            ],
            [
                'label' => 'Черновики',
                'value' => $draftProductsCount,
                'text' => 'Можно довести до публикации',
                'href' => route('seller.products.index', ['status' => 'draft']),
                'icon' => 'ri-draft-line',
                'tone' => $draftProductsCount > 0 ? 'indigo' : 'slate',
            ],
        ];

        $setupChecklist = [
            [
                'label' => 'Заполнить телефон магазина',
                'done' => filled($user->shop?->phone),
                'href' => route('profile.edit'),
            ],
            [
                'label' => 'Подтвердить email',
                'done' => $user->hasVerifiedEmail(),
                'href' => route('profile.edit'),
            ],
            [
                'label' => 'Добавить первый товар',
                'done' => $totalAllProducts > 0,
                'href' => route('seller.products.create'),
            ],
            [
                'label' => 'Выбрать тариф',
                'done' => filled($user->seller_plan ?? null) || filled($user->plan ?? null) || $pendingPlanRequest,
                'href' => route('seller.plans.index'),
            ],
        ];

        return view('seller.cabinet', compact('stats', 'actionCards', 'actionOrders', 'pendingPlanRequest', 'setupChecklist'));
    }
}
