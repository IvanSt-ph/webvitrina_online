<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\AdminActivityLogger;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private readonly AdminActivityLogger $activity)
    {
    }

    /** 🧾 Список отзывов с фильтрацией/сортировкой + счётчики */
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $sort   = $request->get('sort', 'desc');
        $rating = $request->get('rating', 'all');
        $q = trim((string) $request->get('q', ''));

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
            ->when(in_array($rating, ['1', '2', '3', '4', '5'], true), fn ($query) => $query->where('rating', (int) $rating))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('body', 'like', "%{$q}%")
                        ->orWhereHas('user', fn ($user) => $user
                            ->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%"))
                        ->orWhereHas('product', fn ($product) => $product
                            ->where('title', 'like', "%{$q}%")
                            ->orWhere('sku', 'like', "%{$q}%"));
                });
            })
            ->orderBy('created_at', $sort === 'asc' ? 'asc' : 'desc')
            ->paginate(18)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews', 'status', 'sort', 'rating', 'q', 'counters'));
    }

    /** ✅ Одобрить отзыв */
    public function approve(Review $review)
    {
        $review->update([
            'status' => Review::STATUS_APPROVED,
            'rejection_reason' => null,
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);
        $this->activity->log('review.approved', $review, 'Отзыв одобрен администратором.');

        return response()->json(['ok' => true, 'status' => Review::STATUS_APPROVED]);
    }

    /** 🚫 Отклонить отзыв */
    public function reject(Request $request, Review $review)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $review->update([
            'status' => Review::STATUS_REJECTED,
            'rejection_reason' => $data['reason'] ?? null,
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);
        $this->activity->log('review.rejected', $review, 'Отзыв отклонён администратором.', [
            'reason' => $data['reason'],
        ]);

        return response()->json(['ok' => true, 'status' => Review::STATUS_REJECTED]);
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:reviews,id'],
            'action' => ['required', 'in:approve,reject,delete'],
            'reason' => ['nullable', 'required_if:action,reject', 'string', 'max:1000'],
        ]);

        $reviews = Review::whereIn('id', $data['ids']);

        if ($data['action'] === 'delete') {
            $deleted = $reviews->get()->each->delete()->count();
            $this->activity->log('review.bulk_deleted', null, 'Администратор удалил выбранные отзывы.', [
                'count' => $deleted,
            ]);

            return response()->json(['ok' => true, 'deleted' => $deleted]);
        }

        $status = $data['action'] === 'approve'
            ? Review::STATUS_APPROVED
            : Review::STATUS_REJECTED;

        $updated = $reviews->update([
            'status' => $status,
            'rejection_reason' => $status === Review::STATUS_REJECTED ? ($data['reason'] ?? null) : null,
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);
        $this->activity->log('review.bulk_' . $data['action'], null, 'Выполнена массовая модерация отзывов.', [
            'count' => $updated,
            'reason' => $status === Review::STATUS_REJECTED ? ($data['reason'] ?? null) : null,
        ]);

        return response()->json(['ok' => true, 'updated' => $updated, 'status' => $status]);
    }

    /** ❌ Удалить отзыв */
    public function destroy(Review $review)
    {
        $reviewId = $review->id;
        $review->delete();
        $this->activity->log('review.deleted', null, 'Отзыв удалён администратором.', ['review_id' => $reviewId]);
        return response()->json(['deleted' => true]);
    }

    public function show(Review $review)
    {
        return response()->json(
            $review->load(['user', 'product', 'images', 'moderator'])
        );
    }

}
