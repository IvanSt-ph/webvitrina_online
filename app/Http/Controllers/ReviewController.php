<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
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
                'status'      => Review::STATUS_PENDING, // ✅ добавлено: все отзывы ждут модерации
            ]
        );

        // 🧩 Обработка загруженных изображений
        if ($request->hasFile('images')) {
            // Удаляем старые фото, если пользователь редактирует отзыв
            foreach ($review->images as $old) {
                \Storage::disk('public')->delete($old->path);
                $old->delete();
            }

            $manager = new ImageManager(new Driver());

            foreach ($request->file('images') as $imageFile) {
                // Масштабируем изображение (ширина до 1200px)
                $img = $manager->read($imageFile)->scale(width: 1200);

                // Генерируем уникальное имя файла
                $filename = uniqid('rev_') . '.jpg';
                $path = 'reviews/' . $filename;

                // Сохраняем оптимизированное изображение
                $img->save(storage_path('app/public/' . $path), quality: 80);

                // Добавляем запись в таблицу review_images
                $review->images()->create(['path' => $path]);
            }
        }

        // 🧩 Уведомление пользователя
        return back()->with('success', 'Ваш отзыв отправлен на модерацию и появится после проверки администрацией.');
    }
}
