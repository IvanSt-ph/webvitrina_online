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
    public function userReviews()
{
    $reviews = auth()->user()
        ->reviews()
        ->with(['product'])
        ->orderBy('created_at', 'desc')
        ->get();

    return view('buyer.reviews.index', compact('reviews'));
}

}
