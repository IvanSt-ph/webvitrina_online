<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Color;
use App\Services\AttributeDefinitionService;
use App\Services\AttributeHistory;
use App\Services\CategoryFilterCacheService;
use App\Services\ReviewRatingCache;
use Illuminate\Http\Request;
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

        return view('admin.categories.attributes', compact('category', 'attributes', 'colors'));
    }

    /** ➕ Создание */
    public function store(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', 'in:select,text,number,color', function ($field, $value, $fail) use ($request) {
                if ($request->input('name') === 'Цвет' && $value !== 'color') {
                    $fail('Характеристика «Цвет» должна иметь тип color.');
                }
            }],
            'unit' => 'nullable|string|max:50',
            'is_filterable' => 'nullable|boolean',
            'options' => 'nullable|string|max:1000',
            'colors' => [\Illuminate\Validation\Rule::requiredIf($request->input('name') === 'Цвет'), 'nullable', 'array', function ($field, $value, $fail) use ($request) {
                if ($request->input('name') !== 'Цвет' || ! is_array($value)) {
                    return;
                }
                try {
                    \App\Services\AttributeImport\ColorPalette::assertSelection('Цвет', (string) $request->input('type'), $value,
                        Color::orderBy('id')->get(['id', 'name'])->toArray());
                } catch (\App\Services\AttributeImport\ImportException $exception) {
                    $fail($exception->getMessage());
                }
            }],
            'colors.*' => 'integer|exists:colors,id',
        ]);

        try {
            DB::beginTransaction();
            AttributeDefinitionService::lock();
            Category::whereKey($category->id)->lockForUpdate()->firstOrFail();

            // Если это color — options игнорируем
            $options = null;

            if ($validated['type'] !== 'color' && filled($validated['options'] ?? null)) {
                $options = $this->normalizeOptions($validated['options']);
            }

            $attribute = AttributeDefinitionService::findOrCreate([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'unit' => $validated['unit'] ?? null,
                'is_filterable' => $request->boolean('is_filterable', true),
                'options' => $options,
            ], $validated['type'] === 'color' ? ($validated['colors'] ?? []) : []);

            // Привязка категории
            $category->attributes()->syncWithoutDetaching([$attribute->id]);

            // Привязка цветов
            if ($validated['type'] === 'color' && ! empty($validated['colors'])) {
                $attribute->colors()->sync($validated['colors']);
            }

            DB::afterCommit(function () use ($category): void {
                CategoryFilterCacheService::clearForCategoryAndAncestors($category);
                ReviewRatingCache::invalidate();
            });
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
        $category = Category::findOrFail($categoryId);
        $attribute = $category->attributes()->whereKey($attributeId)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', 'in:select,text,number,color', function ($field, $value, $fail) use ($request) {
                if ($request->input('name') === 'Цвет' && $value !== 'color') {
                    $fail('Характеристика «Цвет» должна иметь тип color.');
                }
            }],
            'unit' => 'nullable|string|max:50',
            'is_filterable' => 'nullable|boolean',
            'options' => 'nullable|string|max:1000',
            'colors' => [\Illuminate\Validation\Rule::requiredIf($request->input('name') === 'Цвет'), 'nullable', 'array', function ($field, $value, $fail) use ($request) {
                if ($request->input('name') !== 'Цвет' || ! is_array($value)) {
                    return;
                }
                try {
                    \App\Services\AttributeImport\ColorPalette::assertSelection('Цвет', (string) $request->input('type'), $value,
                        Color::orderBy('id')->get(['id', 'name'])->toArray());
                } catch (\App\Services\AttributeImport\ImportException $exception) {
                    $fail($exception->getMessage());
                }
            }],
            'colors.*' => 'integer|exists:colors,id',
        ]);

        $options = null;
        if ($validated['type'] !== 'color' && filled($validated['options'] ?? null)) {
            $options = $this->normalizeOptions($validated['options']);
        }

        DB::transaction(function () use ($category, $attribute, $validated, $request, $options): void {
            AttributeDefinitionService::lock();
            Category::whereKey($category->id)->lockForUpdate()->firstOrFail();
            $attribute = $category->attributes()->whereKey($attribute->id)->firstOrFail();
            // Definitions with any historical or active values are immutable.
            // A category-local replacement also avoids changing other categories.
            $payload = [
                'name' => $validated['name'],
                'type' => $validated['type'],
                'unit' => $validated['unit'] ?? null,
                'is_filterable' => $request->boolean('is_filterable', true),
                'options' => $options,
            ];
            $oldId = $attribute->id;
            $order = DB::table('attribute_category')->where('category_id', $category->id)
                ->where('attribute_id', $oldId)->value('sort_order');
            $attribute = AttributeDefinitionService::findOrCreate($payload, $validated['type'] === 'color' ? ($validated['colors'] ?? []) : []);
            if ($attribute->id === $oldId) {
                return;
            }
            $category->attributes()->syncWithoutDetaching([$attribute->id => ['sort_order' => $order ?? 0]]);

            // Обновляем цвета
            if ($validated['type'] === 'color') {
                $attribute->colors()->sync($validated['colors'] ?? []);
            } else {
                $attribute->colors()->detach(); // убираем, если тип сменён
            }
            AttributeHistory::detachFromCategory($category->id, [$oldId]);
            $category->attributes()->detach($oldId);
            DB::afterCommit(function () use ($category): void {
                CategoryFilterCacheService::clearForCategoryAndAncestors($category);
                ReviewRatingCache::invalidate();
            });
        });

        return back()->with('success', 'Атрибут успешно обновлён!');
    }

    /** 🗑 Удаление */
    public function destroy(Category $category, Attribute $attribute)
    {
        try {
            DB::beginTransaction();
            AttributeDefinitionService::lock();
            Category::whereKey($category->id)->lockForUpdate()->firstOrFail();

            abort_unless(
                $category->attributes()->whereKey($attribute->id)->exists(),
                404
            );

            AttributeHistory::detachFromCategory($category->id, [$attribute->id]);
            $category->attributes()->detach($attribute->id);

            if ($attribute->categories()->count() === 0 && ! $attribute->values()->exists()) {
                $attribute->colors()->detach();
                $attribute->delete();
            }

            DB::afterCommit(function () use ($category): void {
                CategoryFilterCacheService::clearForCategoryAndAncestors($category);
                ReviewRatingCache::invalidate();
            });
            DB::commit();

            return back()->with('success', 'Атрибут удалён');

        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', $e->getMessage());
        }
    }

    private function normalizeOptions(string $options): array
    {
        return collect(preg_split('/[\r\n,]+/', $options))
            ->map(fn ($value) => trim($value))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values()
            ->toArray();
    }
}
