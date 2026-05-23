<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\User;

class PublicUserController extends Controller
{
    public function show(User $user)
    {
        $user->load([
            'shop' => fn ($query) => $query->withCount('followers'),
        ]);

        $publicStats = [
            'seller_products' => $user->isSeller()
                ? $user->products()->active()->count()
                : 0,
            'seller_reviews' => $user->isSeller()
                ? $user->reviews()->where('reviews.status', Review::STATUS_APPROVED)->count()
                : 0,
            'written_reviews' => Review::where('user_id', $user->id)
                ->where('status', Review::STATUS_APPROVED)
                ->count(),
            'completed_orders' => $user->orders()->where('status', 'completed')->count(),
        ];

        $publicReviews = Review::query()
            ->where('user_id', $user->id)
            ->where('status', Review::STATUS_APPROVED)
            ->with(['product:id,slug,title,image'])
            ->latest()
            ->limit(4)
            ->get();

        return view('users.show', compact('user', 'publicStats', 'publicReviews'));
    }
}
