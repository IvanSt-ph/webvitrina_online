<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\AdminActivityLogger;
use App\Services\UserNotificationService;
use App\Services\ReviewModerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        foreach ($reviews as $review) {
            $review->setAttribute('content_fingerprint', $review->moderationFingerprint());
        }

        return view('admin.reviews.index', compact('reviews', 'status', 'sort', 'rating', 'q', 'counters'));
    }

    /** ✅ Одобрить отзыв */
    public function approve(Request $request, Review $review)
    {
        $data = $request->validate(['content_fingerprint' => ['required', 'string', 'size:64']]);
        $review = app(ReviewModerationService::class)->moderate(
            [$review->id], [$review->id => $data['content_fingerprint']], Review::STATUS_APPROVED, auth()->id()
        )->first();
        $this->activity->log('review.approved', $review, 'Отзыв одобрен администратором.');
        app(UserNotificationService::class)->create(
            $review->user,
            'review_approved',
            'Отзыв опубликован',
            'Ваш отзыв прошёл модерацию и появился на странице товара.',
            $review->product ? route('product.show', $review->product->slug, false) . '#reviews' : null,
            ['review_id' => $review->id]
        );

        return response()->json(['ok' => true, 'status' => Review::STATUS_APPROVED]);
    }

    /** 🚫 Отклонить отзыв */
    public function reject(Request $request, Review $review)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'content_fingerprint' => ['required', 'string', 'size:64'],
        ]);

        $review = app(ReviewModerationService::class)->moderate(
            [$review->id], [$review->id => $data['content_fingerprint']], Review::STATUS_REJECTED, auth()->id(), $data['reason']
        )->first();
        $this->activity->log('review.rejected', $review, 'Отзыв отклонён администратором.', [
            'reason' => $data['reason'],
        ]);
        app(UserNotificationService::class)->create(
            $review->user,
            'review_rejected',
            'Отзыв нужно исправить',
            'Причина: ' . $data['reason'],
            route('reviews.index', [], false),
            ['review_id' => $review->id]
        );

        return response()->json(['ok' => true, 'status' => Review::STATUS_REJECTED]);
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:reviews,id'],
            'action' => ['required', 'in:approve,reject,delete'],
            'reason' => ['nullable', 'required_if:action,reject', 'string', 'max:1000'],
            'fingerprints' => ['required_unless:action,delete', 'array'],
            'fingerprints.*' => ['required', 'string', 'size:64'],
        ]);

        if ($data['action'] === 'delete') {
            $deleted = DB::transaction(fn () => Review::whereIn('id', $data['ids'])
                ->orderBy('id')->lockForUpdate()->get()->each->delete()->count());
            $this->activity->log('review.bulk_deleted', null, 'Администратор удалил выбранные отзывы.', [
                'count' => $deleted,
            ]);

            return response()->json(['ok' => true, 'deleted' => $deleted]);
        }

        $status = $data['action'] === 'approve'
            ? Review::STATUS_APPROVED
            : Review::STATUS_REJECTED;

        $reviews = app(ReviewModerationService::class)->moderate(
            $data['ids'], $data['fingerprints'], $status, auth()->id(), $data['reason'] ?? null
        );
        $updated = $reviews->count();
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
        DB::transaction(fn () => Review::whereKey($reviewId)->lockForUpdate()->firstOrFail()->delete());
        $this->activity->log('review.deleted', null, 'Отзыв удалён администратором.', ['review_id' => $reviewId]);
        return response()->json(['deleted' => true]);
    }

    public function show(Review $review)
    {
        $review->load(['user', 'product', 'images', 'moderator']);
        $review->setAttribute('content_fingerprint', $review->moderationFingerprint());
        return response()->json($review);
    }

}
