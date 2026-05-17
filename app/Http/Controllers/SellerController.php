<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use App\Models\Review;
use App\Models\Conversation;

class SellerController extends Controller
{
    private const RECENT_MESSAGES_LIMIT = 50;

    public function show(string $identifier)
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

        // Товары продавца
        $productsQuery = $user->products()
            ->active()
            ->with(['category', 'seller.shop', 'city.country'])
            ->withCount([
                'reviews as reviews_count' => fn($q) => $q->where('status', 'approved'),
            ])
            ->withAvg([
                'reviews as reviews_avg_rating' => fn($q) => $q->where('status', 'approved'),
            ], 'rating');

        if (auth()->check()) {
            $productsQuery
                ->withSum([
                    'cartItems as cart_quantity' => fn($q) => $q->where('user_id', auth()->id()),
                ], 'qty')
                ->withExists([
                    'favorites as is_favorited' => fn($q) => $q->where('user_id', auth()->id()),
                ]);
        }

        $products = $productsQuery
            ->latest()
            ->paginate(12);

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
            'chatLatestReadOutgoingMessageId'
        ));
    }
}
