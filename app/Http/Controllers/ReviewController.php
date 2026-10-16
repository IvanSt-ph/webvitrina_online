<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use App\Models\Order;
use App\Rules\ImageUploadConstraints;
use App\Repositories\ProductRepository;
use App\Services\ImageService;
use App\Services\ReviewImageOperation;
use App\Services\ReviewImageStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function __construct(protected ImageService $images) {}

    /**
     * Создание или обновление отзыва пользователем
     */
public function store(Request $request, Product $product)
{
    abort_if($product->status !== Product::STATUS_ACTIVE, 404);

    if ($response = $this->rejectOwnProduct($request, $product)) {
        return $response;
    }

    $hasPurchased = Order::where('user_id', auth()->id())
        ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
        ->where(function ($workflow) {
            $workflow->whereNull('workflow_version')
                ->orWhere(function ($pickup) {
                    $pickup->where('workflow_version', Order::WORKFLOW_PICKUP)
                        ->whereNotNull('buyer_confirmed_at');
                });
        })
        ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
        ->exists();

    if (! $hasPurchased) {
        return $this->purchaseRequiredResponse($request);
    }

    return $this->persistReview($request, $product);
}

public function storeForOrder(Request $request, Order $order, string $product)
{
    abort_unless($order->user_id === $request->user()->id, 403);
    abort_unless(ctype_digit($product), 404);

    $item = $order->items()
        ->without('product')
        ->where('product_id', (int) $product)
        ->firstOrFail();
    $product = Product::withTrashed()->findOrFail($item->product_id);

    $canReview = in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], true)
        && ($order->workflow_version === null
            || ($order->workflow_version === Order::WORKFLOW_PICKUP && $order->buyer_confirmed_at !== null));

    if (! $canReview) {
        return $this->purchaseRequiredResponse($request);
    }

    if ($response = $this->rejectOwnProduct($request, $product)) {
        return $response;
    }

    return $this->persistReview($request, $product);
}

private function persistReview(Request $request, Product $product)
{
    // 🧩 Валидация данных формы
    $data = $request->validate([
        'rating'    => 'required|integer|min:1|max:5',
        'body'      => 'nullable|string|max:2000',
        'images'    => 'array|max:3',
        'images.*'  => ImageUploadConstraints::rules(4096, required: true),
    ]);

    // 🧩 Создание или обновление отзыва (один отзыв на пользователя)
    // Reputation recalculation is registered by the observer for afterCommit,
    // so it always starts with a fresh REPEATABLE READ snapshot.
    $review = DB::transaction(function () use ($request, $product, $data) {
        $review = Review::withTrashed()->updateOrCreate(
            ['user_id' => auth()->id(), 'product_id' => $product->id],
            [
                'rating'      => $data['rating'],
                'body'        => $data['body'] ?? null,
                'user_id'     => auth()->id(),
                'product_id'  => $product->id,
                'status'      => Review::STATUS_PENDING,
                'rejection_reason' => null,
                'moderated_by' => null,
                'moderated_at' => null,
            ]
        );

        if ($review->trashed()) {
            $review->restore();
        }

        // 🧩 Обработка загруженных изображений
        if ($request->hasFile('images')) {
            $files = new ReviewImageOperation(
                $this->images,
                app(ReviewImageStorage::class),
                DB::connection(),
            );
            $newImagePaths = $files->uploadGallery(
                $request->file('images'),
                $this->images->makeDir('reviews')
            );

            // Database rows change atomically. Old files are retained until commit.
            foreach ($review->images as $old) {
                $files->deleteAfterCommit($old->path);
                $old->delete();
            }

            foreach ($newImagePaths as $path) {
                $review->images()->create([
                    'path' => $path,
                ]);
            }
        }

        return $review;
    });

    // 🧹 Очистка кэша товара
    ProductRepository::clearProductCache($product);

    // 🧩 Уведомление пользователя
    return back()->with('success', 'Ваш отзыв отправлен на модерацию и появится после проверки администрацией.');
}

private function rejectOwnProduct(Request $request, Product $product)
{
    if ($product->user_id !== $request->user()->id) {
        return null;
    }

    $message = 'Нельзя оставить отзыв на собственный товар.';

    if (! $request->expectsJson()) {
        return back()->with('warning', $message);
    }

    throw ValidationException::withMessages(['review' => $message]);
}

private function purchaseRequiredResponse(Request $request)
{
    $message = 'Отзыв можно оставить только после покупки и получения товара.';

    if (! $request->expectsJson()) {
        return back()->with('warning', $message);
    }

    throw ValidationException::withMessages(['review' => $message]);
}


    // Отзывы покупателя
    public function userReviews(Request $request)
{
    $status = $request->query('status', 'all');
    $rating = $request->query('rating', 'all');
    $sort = $request->query('sort', 'new');
    $allowedStatuses = [
        'all',
        Review::STATUS_APPROVED,
        Review::STATUS_PENDING,
        Review::STATUS_REJECTED,
    ];

    if (! in_array($status, $allowedStatuses, true)) {
        $status = 'all';
    }

    if (! in_array($rating, ['all', '1', '2', '3', '4', '5'], true)) {
        $rating = 'all';
    }

    if (! in_array($sort, ['new', 'old', 'high', 'low'], true)) {
        $sort = 'new';
    }

    $baseQuery = Review::query()
        ->where('user_id', auth()->id());

    $rawCounters = (clone $baseQuery)
        ->selectRaw('status, COUNT(*) as total')
        ->groupBy('status')
        ->pluck('total', 'status');

    $counters = [
        'all' => (int) $rawCounters->sum(),
        Review::STATUS_APPROVED => (int) ($rawCounters[Review::STATUS_APPROVED] ?? 0),
        Review::STATUS_PENDING => (int) ($rawCounters[Review::STATUS_PENDING] ?? 0),
        Review::STATUS_REJECTED => (int) ($rawCounters[Review::STATUS_REJECTED] ?? 0),
    ];

    $avgRating = (float) (clone $baseQuery)->avg('rating');

    $reviews = $baseQuery
        ->when($status !== 'all', fn ($query) => $query->where('status', $status))
        ->when($rating !== 'all', fn ($query) => $query->where('rating', (int) $rating))
        ->with(['product', 'images'])
        ->when($sort === 'old', fn ($query) => $query->orderBy('created_at', 'asc'))
        ->when($sort === 'high', fn ($query) => $query->orderBy('rating', 'desc')->orderBy('created_at', 'desc'))
        ->when($sort === 'low', fn ($query) => $query->orderBy('rating', 'asc')->orderBy('created_at', 'desc'))
        ->when($sort === 'new', fn ($query) => $query->orderBy('created_at', 'desc'))
        ->get();

    return view('buyer.reviews.index', compact('reviews', 'status', 'rating', 'sort', 'counters', 'avgRating'));
}

}
