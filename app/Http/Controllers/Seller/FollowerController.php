<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FollowerController extends Controller
{
    public function index(Request $request)
    {
        $shop = $request->user()
            ->shop()
            ->withCount('followers')
            ->first();

        $followers = $shop
            ? $shop->followers()
                ->with('shop')
                ->withPivot('created_at')
                ->orderByDesc('shop_followers.created_at')
                ->paginate(24)
                ->withQueryString()
            : collect();

        $stats = [
            'total' => $shop?->followers_count ?? 0,
            'buyers' => $shop ? (clone $shop->followers())->where('role', 'buyer')->count() : 0,
            'sellers' => $shop ? (clone $shop->followers())->where('role', 'seller')->count() : 0,
            'recent' => $shop
                ? (clone $shop->followers())->wherePivot('created_at', '>=', now()->subDays(30))->count()
                : 0,
        ];

        return view('seller.followers.index', compact('shop', 'followers', 'stats'));
    }
}
