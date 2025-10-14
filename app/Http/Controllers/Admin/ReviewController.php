<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /** 🧾 Список отзывов с фильтрацией/сортировкой + счётчики */
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $sort   = $request->get('sort', 'desc');

        // Счётчики по статусам
        $raw = Review::selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $counters = [
            'all'      => ($raw['pending'] ?? 0) + ($raw['approved'] ?? 0) + ($raw['rejected'] ?? 0),
            'pending'  => $raw['pending']  ?? 0,
            'approved' => $raw['approved'] ?? 0,
            'rejected' => $raw['rejected'] ?? 0,
        ];

        $reviews = Review::with(['user', 'product', 'images'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderBy('created_at', $sort === 'asc' ? 'asc' : 'desc')
            ->paginate(18)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews', 'status', 'sort', 'counters'));
    }

    /** ✅ Одобрить отзыв */
    public function approve(Review $review)
    {
        $review->update(['status' => Review::STATUS_APPROVED]);
        return response()->json(['ok' => true, 'status' => Review::STATUS_APPROVED]);
    }

    /** 🚫 Отклонить отзыв */
    public function reject(Review $review)
    {
        $review->update(['status' => Review::STATUS_REJECTED]);
        return response()->json(['ok' => true, 'status' => Review::STATUS_REJECTED]);
    }

    /** ❌ Удалить отзыв */
    public function destroy(Review $review)
    {
        $review->delete();
        return response()->json(['deleted' => true]);
    }

    public function show(Review $review)
{
    return response()->json(
        $review->load(['user', 'product', 'images'])
    );
}

}
