<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ReviewController extends Controller
{
    /**
     * Создание или обновление отзыва пользователем
     */
public function store(Request $request, Product $product)
{
    // 🧩 Валидация данных формы
    $data = $request->validate([
        'rating'    => 'required|integer|min:1|max:5',
        'body'      => 'nullable|string|max:2000',
        'images'    => 'array|max:3',
        'images.*'  => 'image|max:5120', // до 5МБ
    ]);

    // 🧩 Создание или обновление отзыва (один отзыв на пользователя)
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
        // Удаляем старые изображения
        foreach ($review->images as $old) {
            \Storage::disk('public')->delete($old->path);
            $old->delete();
        }

        // ✅ СОЗДАЁМ ДИРЕКТОРИЮ, ЕСЛИ ЕЁ НЕТ
        $reviewsDir = storage_path('app/public/reviews');
        if (!file_exists($reviewsDir)) {
            mkdir($reviewsDir, 0755, true);
        }

        $manager = new ImageManager(new Driver());

        foreach ($request->file('images') as $imageFile) {
            $img = $manager->read($imageFile)->scale(width: 1200);
            $filename = uniqid('rev_') . '.jpg';
            $path = 'reviews/' . $filename;
            
            // ✅ ПРОВЕРЯЕМ СУЩЕСТВОВАНИЕ ДИРЕКТОРИИ ПЕРЕД СОХРАНЕНИЕМ
            $fullPath = storage_path('app/public/' . $path);
            $dir = dirname($fullPath);
            
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $img->save($fullPath, quality: 80);
            $review->images()->create(['path' => $path]);
        }
    }

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
