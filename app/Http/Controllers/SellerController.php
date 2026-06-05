<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use App\Models\Review;
use App\Models\Conversation;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    private const RECENT_MESSAGES_LIMIT = 50;

    public function show(string $identifier, Request $request)
    {
        if (ctype_digit($identifier)) {
            $user = User::findOrFail((int) $identifier);

            if ($user->shop?->slug) {
                return redirect()
                    ->route('seller.show', $user->shop->slug)
                    ->setStatusCode(301);
            }
        } else {
            $shop = Shop::where('slug', $identifier)->firstOrFail();
            $user = $shop->user;
        }

        // Магазин продавца
        $shop = $user->shop;
        $shop?->loadCount('followers');
        $isFollowingShop = auth()->check() && $shop
            ? $shop->isFollowedBy(auth()->user())
            : false;

        $filter = $request->query('filter', 'all');
        if (! in_array($filter, ['all', 'new', 'sale', 'hit'], true)) {
            $filter = 'all';
        }

        // Товары продавца
        $baseProductsQuery = $user->products()
            ->active()
            ->with(['category', 'seller.shop', 'city.country'])
            ->withCount([
                'reviews as reviews_count' => fn($q) => $q->where('status', 'approved'),
            ])
            ->withAvg([
                'reviews as reviews_avg_rating' => fn($q) => $q->where('status', 'approved'),
            ], 'rating');

        if (auth()->check()) {
            $baseProductsQuery
                ->withSum([
                    'cartItems as cart_quantity' => fn($q) => $q->where('user_id', auth()->id()),
                ], 'qty')
                ->withExists([
                    'favorites as is_favorited' => fn($q) => $q->where('user_id', auth()->id()),
                ]);
        }

        $filterCounts = [
            'all' => (clone $baseProductsQuery)->count(),
            'new' => (clone $baseProductsQuery)->where('created_at', '>=', now()->subDays(30))->count(),
            'sale' => (clone $baseProductsQuery)->whereColumn('old_price', '>', 'price')->count(),
            'hit' => (clone $baseProductsQuery)
                ->where(function ($query) {
                    $query->where('views_count', '>=', 20)
                        ->orWhereHas('reviews', fn ($reviews) => $reviews->where('status', 'approved'));
                })
                ->count(),
        ];

        $productsQuery = clone $baseProductsQuery;

        $productsQuery
            ->when($filter === 'new', fn ($query) => $query->where('created_at', '>=', now()->subDays(30)))
            ->when($filter === 'sale', fn ($query) => $query->whereColumn('old_price', '>', 'price'))
            ->when($filter === 'hit', function ($query) {
                $query->where(function ($inner) {
                    $inner->where('views_count', '>=', 20)
                        ->orWhereHas('reviews', fn ($reviews) => $reviews->where('status', 'approved'));
                });
            });

        $products = $productsQuery
            ->when($filter === 'sale', fn ($query) => $query->orderByDesc('created_at'))
            ->when($filter === 'hit', fn ($query) => $query->orderByDesc('views_count')->orderByDesc('reviews_count'))
            ->when(! in_array($filter, ['sale', 'hit'], true), fn ($query) => $query->latest())
            ->paginate(12);
        $products->appends(['filter' => $filter]);

        $recommendedProducts = (clone $baseProductsQuery)
            ->orderByDesc('views_count')
            ->orderByDesc('reviews_count')
            ->latest()
            ->limit(10)
            ->get();

        // Отзывы по товарам продавца
        $reviews = Review::whereHas('product', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('status', 'approved')
            ->latest()
            ->limit(10)
            ->get();

        $chatConversation = null;
        $chatMessages = collect();
        $chatHasOlderMessages = false;
        $chatOldestMessageId = null;
        $chatLatestMessageId = null;
        $chatLatestReadOutgoingMessageId = 0;

        if (auth()->check() && request()->filled('chat')) {
            $chatConversation = Conversation::with(['buyer', 'seller'])
                ->findOrFail(request()->integer('chat'));

            abort_unless($chatConversation->includes(auth()->user()), 403);
            abort_unless($chatConversation->seller_id === $user->id, 404);

            $chatConversation->messages()
                ->where('sender_id', '!=', auth()->id())
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $chatMessages = $chatConversation->recentMessages(self::RECENT_MESSAGES_LIMIT);
            $chatHasOlderMessages = $chatConversation->hasMoreThanRecentMessages(self::RECENT_MESSAGES_LIMIT);
            $chatOldestMessageId = $chatMessages->first()?->id;
            $chatLatestMessageId = $chatMessages->last()?->id;
            $chatLatestReadOutgoingMessageId = $chatConversation->latestReadOutgoingMessageIdFor(auth()->user());
        }

        return view('seller.show', compact(
            'user',
            'shop',
            'products',
            'reviews',
            'chatConversation',
            'chatMessages',
            'chatHasOlderMessages',
            'chatOldestMessageId',
            'chatLatestMessageId',
            'chatLatestReadOutgoingMessageId',
            'isFollowingShop',
            'recommendedProducts'
        ) + compact('filter', 'filterCounts'));
    }
}
