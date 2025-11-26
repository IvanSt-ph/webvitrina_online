<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\Color;
use Illuminate\Support\Facades\DB;

class CategoryAttributeController extends Controller
{
    /** 📄 Показ */
public function index(Category $category)
{
    $attributes = $category->attributes()
        ->with('colors')
        ->orderBy('name')
        ->get();

    $colors = \App\Models\Color::all();

    return view('admin.categories.attributes', compact('category','attributes','colors'));
}


    /** ➕ Создание */
    public function store(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'type'    => 'required|in:select,text,number,color',
            'options' => 'nullable|string|max:500',
            'colors'  => 'nullable|array',
            'colors.*'=> 'integer|exists:colors,id'
        ]);

        try {
            DB::beginTransaction();

            // Если это color — options игнорируем
            $options = null;

            if ($validated['type'] !== 'color' && !empty($validated['options'])) {
                $options = collect(explode(',', $validated['options']))
                    ->map(fn($v) => trim($v))
                    ->filter()
                    ->values()
                    ->toArray();
            }

            $attribute = Attribute::create([
                'name'    => $validated['name'],
                'type'    => $validated['type'],
                'options' => $options,
            ]);

            // Привязка категории
            $category->attributes()->attach($attribute->id);

            // Привязка цветов
            if ($validated['type'] === 'color' && !empty($validated['colors'])) {
                $attribute->colors()->sync($validated['colors']);
            }

            DB::commit();
            return back()->with('success', 'Атрибут успешно добавлен!');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /** ✏️ Обновление */
    public function update(Request $request, $categoryId, $attributeId)
    {
        $attribute = Attribute::findOrFail($attributeId);

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'type'    => 'required|in:select,text,number,color',
            'options' => 'nullable|string|max:500',
            'colors'  => 'nullable|array',
            'colors.*'=> 'integer|exists:colors,id'
        ]);

        $options = null;
        if ($validated['type'] !== 'color' && !empty($validated['options'])) {
            $options = collect(explode(',', $validated['options']))
                ->map(fn($v) => trim($v))
                ->filter()
                ->values()
                ->toArray();
        }

        $attribute->update([
            'name'    => $validated['name'],
            'type'    => $validated['type'],
            'options' => $options,
        ]);

        // Обновляем цвета
        if ($validated['type'] === 'color') {
            $attribute->colors()->sync($validated['colors'] ?? []);
        } else {
            $attribute->colors()->detach(); // убираем, если тип сменён
        }

        return back()->with('success', 'Атрибут успешно обновлён!');
    }

    /** 🗑 Удаление */
    public function destroy(Category $category, Attribute $attribute)
    {
        try {
            DB::beginTransaction();

            $category->attributes()->detach($attribute->id);
            $attribute->colors()->detach();

            if ($attribute->categories()->count() === 0) {
                $attribute->delete();
            }

            DB::commit();
            return back()->with('success', 'Атрибут удалён');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}
