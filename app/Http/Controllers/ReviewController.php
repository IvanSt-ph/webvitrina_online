<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use App\Models\Order;
use App\Repositories\ProductRepository;
use App\Services\ImageService;
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
    abort_if($product->status !== 'active', 404);

    if ($product->user_id === auth()->id()) {
        throw ValidationException::withMessages([
            'review' => 'Нельзя оставить отзыв на собственный товар.',
        ]);
    }

    $hasPurchased = Order::where('user_id', auth()->id())
        ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
        ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
        ->exists();

    if (! $hasPurchased) {
        throw ValidationException::withMessages([
            'review' => 'Отзыв можно оставить только после покупки и получения товара.',
        ]);
    }

    // 🧩 Валидация данных формы
    $data = $request->validate([
        'rating'    => 'required|integer|min:1|max:5',
        'body'      => 'nullable|string|max:2000',
        'images'    => 'array|max:3',
        'images.*'  => 'image|mimes:jpg,jpeg,png,webp|max:4096',
    ]);

    // 🧩 Создание или обновление отзыва (один отзыв на пользователя)
    $review = DB::transaction(function () use ($request, $product, $data) {
        $review = Review::updateOrCreate(
            ['user_id' => auth()->id(), 'product_id' => $product->id],
            [
                'rating'      => $data['rating'],
                'body'        => $data['body'] ?? null,
                'user_id'     => auth()->id(),
                'product_id'  => $product->id,
                'status'      => Review::STATUS_PENDING,
            ]
        );

        // 🧩 Обработка загруженных изображений
        if ($request->hasFile('images')) {
            // Удаляем старые изображения вместе с миниатюрами
            foreach ($review->images as $old) {
                $this->images->delete($old->path);
                $old->delete();
            }

            foreach ($request->file('images') as $imageFile) {
                $review->images()->create([
                    'path' => $this->images->upload($imageFile, $this->images->makeDir('reviews')),
                ]);
            }
        }

        return $review;
    });

    // 🧹 Очистка кэша товара
    ProductRepository::clearProductCache($review->product);

    // 🧩 Уведомление пользователя
    return back()->with('success', 'Ваш отзыв отправлен на модерацию и появится после проверки администрацией.');
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
