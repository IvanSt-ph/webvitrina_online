<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;

class ShopFollowController extends Controller
{
    public function toggle(Request $request, Shop $shop)
    {
        abort_if($shop->user_id === $request->user()->id, 422, 'Нельзя подписаться на свой магазин.');

        $currentlyFollowing = $shop->followers()
            ->whereKey($request->user()->id)
            ->exists();

        if ($currentlyFollowing) {
            $shop->followers()->detach($request->user()->id);
            $message = 'Подписка на магазин отменена.';
            $following = false;
        } else {
            $shop->followers()->attach($request->user()->id);
            $message = 'Вы подписались на магазин.';
            $following = true;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'following' => $following,
                'followers_count' => $shop->followers()->count(),
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
