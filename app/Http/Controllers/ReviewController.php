<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        // 🧩 Валидация
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'body'   => 'nullable|string|max:2000',
            'images' => 'array|max:3',
            'images.*' => 'image|max:5120',
        ]);

        // 🧩 Создание или обновление отзыва
        $review = Review::updateOrCreate(
            ['user_id' => auth()->id(), 'product_id' => $product->id],
            [
                'rating' => $data['rating'],
                'body' => $data['body'] ?? null,
                'user_id' => auth()->id(),
                'product_id' => $product->id,
            ]
        );

        // 🧩 Обработка изображений
if ($request->hasFile('images')) {
    // удалить старые фото
    foreach ($review->images as $old) {
        \Storage::disk('public')->delete($old->path);
        $old->delete();
    }

    $manager = new ImageManager(new Driver());
    foreach ($request->file('images') as $imageFile) {
        $img = $manager->read($imageFile)->scale(width: 1200);
        $filename = uniqid('rev_') . '.jpg';
        $path = 'reviews/' . $filename;
        $img->save(storage_path('app/public/' . $path), quality: 80);
        $review->images()->create(['path' => $path]);
    }
}


        return back()->with('success', 'Отзыв успешно сохранён!');
    }
}
