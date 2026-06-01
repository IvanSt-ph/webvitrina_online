<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\Color;
use App\Services\CategoryFilterCacheService;
use Illuminate\Support\Facades\DB;

class CategoryAttributeController extends Controller
{
    /** 📄 Показ */
public function index(Category $category)
{
    $category->load(['parent.parent.parent'])
        ->loadCount(['children', 'products']);

    $attributes = $category->attributes()
        ->with('colors')
        ->orderBy('name')
        ->get();

    $colors = Color::orderBy('name')->get();

    return view('admin.categories.attributes', compact('category','attributes','colors'));
}


    /** ➕ Создание */
    public function store(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'type'          => 'required|in:select,text,number,color',
            'unit'          => 'nullable|string|max:50',
            'is_filterable' => 'nullable|boolean',
            'options'       => 'nullable|string|max:1000',
            'colors'        => 'nullable|array',
            'colors.*'      => 'integer|exists:colors,id'
        ]);

        try {
            DB::beginTransaction();

            // Если это color — options игнорируем
            $options = null;

            if ($validated['type'] !== 'color' && !empty($validated['options'])) {
                $options = $this->normalizeOptions($validated['options']);
            }

            $attribute = Attribute::create([
                'name'          => $validated['name'],
                'type'          => $validated['type'],
                'unit'          => $validated['unit'] ?? null,
                'is_filterable' => $request->boolean('is_filterable', true),
                'options'       => $options,
            ]);

            // Привязка категории
            $category->attributes()->attach($attribute->id);

            // Привязка цветов
            if ($validated['type'] === 'color' && !empty($validated['colors'])) {
                $attribute->colors()->sync($validated['colors']);
            }

            DB::commit();
            CategoryFilterCacheService::clearForCategoryAndAncestors($category);
            return back()->with('success', 'Атрибут успешно добавлен!');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /** ✏️ Обновление */
    public function update(Request $request, $categoryId, $attributeId)
    {
        $category = Category::findOrFail($categoryId);
        $attribute = $category->attributes()->whereKey($attributeId)->firstOrFail();

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'type'          => 'required|in:select,text,number,color',
            'unit'          => 'nullable|string|max:50',
            'is_filterable' => 'nullable|boolean',
            'options'       => 'nullable|string|max:1000',
            'colors'        => 'nullable|array',
            'colors.*'      => 'integer|exists:colors,id'
        ]);

        $options = null;
        if ($validated['type'] !== 'color' && !empty($validated['options'])) {
            $options = $this->normalizeOptions($validated['options']);
        }

        $attribute->update([
            'name'          => $validated['name'],
            'type'          => $validated['type'],
            'unit'          => $validated['unit'] ?? null,
            'is_filterable' => $request->boolean('is_filterable', true),
            'options'       => $options,
        ]);

        // Обновляем цвета
        if ($validated['type'] === 'color') {
            $attribute->colors()->sync($validated['colors'] ?? []);
        } else {
            $attribute->colors()->detach(); // убираем, если тип сменён
        }

        CategoryFilterCacheService::clearForCategoryAndAncestors($category);

        return back()->with('success', 'Атрибут успешно обновлён!');
    }

    /** 🗑 Удаление */
    public function destroy(Category $category, Attribute $attribute)
    {
        try {
            DB::beginTransaction();

            abort_unless(
                $category->attributes()->whereKey($attribute->id)->exists(),
                404
            );

            $category->attributes()->detach($attribute->id);

            if ($attribute->categories()->count() === 0) {
                $attribute->colors()->detach();
                $attribute->delete();
            }

            DB::commit();
            CategoryFilterCacheService::clearForCategoryAndAncestors($category);
            return back()->with('success', 'Атрибут удалён');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    private function normalizeOptions(string $options): array
    {
        return collect(preg_split('/[\r\n,]+/', $options))
            ->map(fn($value) => trim($value))
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }
}
