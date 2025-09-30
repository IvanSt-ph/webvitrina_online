<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Country;
use Illuminate\Http\Request;

class ProductManageController extends Controller
{
    /**
     * Список товаров текущего продавца.
     */
    public function index()
    {
        $products = Product::where('user_id', auth()->id())
            ->with(['category', 'city.country'])
            ->latest()
            ->paginate(15);

        return view('seller.products.index', compact('products'));
    }

    /**
     * Форма создания товара.
     */
    public function create()
    {
        // Корневые категории и список стран для селектов
        $rootCategories = Category::whereNull('parent_id')->orderBy('name')->get();
        $countries = Country::orderBy('name')->get();

        // Передаём "пустую" модель в форму
        return view('seller.products.form', [
            'product'        => new Product(),
            'rootCategories' => $rootCategories,
            'countries'      => $countries,
        ]);
    }

    /**
     * Сохранение нового товара.
     * ВАЖНО: здесь обрабатываем и image, и gallery[].
     */
    public function store(Request $request)
    {
        // 1) Валидация входных данных.
        //    Обрати внимание на 'gallery' => 'array' + 'gallery.*' => 'image'
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'price'        => 'required|numeric|min:0',
            'stock'        => 'required|integer|min:0',
            'description'  => 'nullable|string',
            'image'        => 'nullable|image|max:2048',
            'gallery'      => 'nullable|array',
            'gallery.*'    => 'nullable|image|max:2048',
            'category_id'  => 'required|exists:categories,id',
            'city_id'      => 'required|exists:cities,id',
        ]);

        // 2) Сохраняем главное фото (одно).
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        // 3) Сохраняем галерею (несколько).
        //    На входе name="gallery[]" → $request->file('gallery') вернёт массив UploadedFile.
        if ($request->hasFile('gallery')) {
            $gallery = [];
            foreach ($request->file('gallery') as $file) {
                if ($file) {
                    $gallery[] = $file->store('products/gallery', 'public');
                }
            }
            $data['gallery'] = $gallery; // это массив, в БД попадёт JSON (casts в модели)
        }

        // 4) Привязываем товар к текущему продавцу.
        $data['user_id'] = auth()->id();

        // 5) Создаём товар.
        Product::create($data);

        return redirect()->route('seller.products.index')->with('success', 'Товар создан');
    }

    /**
     * Форма редактирования товара.
     */
    public function edit(Product $product)
    {
        // Защита: нельзя редактировать чужой товар
        $this->authorize('update', $product);

        $rootCategories = Category::whereNull('parent_id')->orderBy('name')->get();
        $countries      = Country::orderBy('name')->get();

        return view('seller.products.form', compact('product', 'rootCategories', 'countries'));
    }

    /**
     * Обновление товара.
     * ВАЖНО: новые фото галереи ДОБАВЛЯЕМ к существующим, а не затираем.
     */
    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        // 1) Валидация
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'price'        => 'required|numeric|min:0',
            'stock'        => 'required|integer|min:0',
            'description'  => 'nullable|string',
            'image'        => 'nullable|image|max:2048',
            'gallery'      => 'nullable|array',
            'gallery.*'    => 'nullable|image|max:2048',
            'category_id'  => 'required|exists:categories,id',
            'city_id'      => 'required|exists:cities,id',
        ]);

        // 2) Обновляем главное фото (если загружено)
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        // 3) Обновляем галерею:
        //    - берём уже сохранённые (если были) из модели
        //    - добавляем новые из запроса
        $currentGallery = (array) ($product->gallery ?? []);
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                if ($file) {
                    $currentGallery[] = $file->store('products/gallery', 'public');
                }
            }
        }
        // Если были новые — кладём обратно в $data
        if (!empty($currentGallery)) {
            $data['gallery'] = $currentGallery;
        }

        // 4) Сохраняем изменения
        $product->update($data);

        return redirect()->route('seller.products.index')->with('success', 'Товар обновлён');
    }

    /**
     * Удаление товара.
     */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()->route('seller.products.index')->with('success', 'Товар удалён');
    }
}
