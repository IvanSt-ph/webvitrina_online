<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Attribute;
use Illuminate\Support\Facades\DB;

class CategoryAttributeController extends Controller
{
    public function index(Category $category)
    {
        $attributes = $category->attributes()->orderBy('name')->get();
        return view('admin.categories.attributes', compact('category', 'attributes'));
    }

    public function store(Request $request, Category $category)
    {
        // ✅ Безопасная валидация
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:select,text,number,color',
            'options' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // ✅ Преобразуем options в массив, если есть значения
            $options = null;
            if (!empty($validated['options'])) {
                $options = collect(explode(',', $validated['options']))
                    ->map(fn($v) => trim($v))
                    ->filter()
                    ->values()
                    ->toArray();
            }

            // ✅ Создаём атрибут
            $attribute = Attribute::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'options' => $options,
            ]);

            // ✅ Привязываем его к категории (через pivot)
            $category->attributes()->attach($attribute->id);

            DB::commit();

            return redirect()
                ->route('admin.categories.attributes', $category->id)
                ->with('success', '✅ Атрибут успешно добавлен!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', '❌ Ошибка: ' . $e->getMessage());
        }
    }

    public function destroy(Category $category, Attribute $attribute)
    {
        try {
            DB::beginTransaction();

            // ✅ Убираем связь с категорией
            $category->attributes()->detach($attribute->id);

            // ✅ Если больше ни с чем не связан — удаляем полностью
            if ($attribute->categories()->count() === 0) {
                $attribute->delete();
            }

            DB::commit();
            return back()->with('success', '🗑 Атрибут успешно удалён');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', '❌ Ошибка при удалении: ' . $e->getMessage());
        }
    }
}
