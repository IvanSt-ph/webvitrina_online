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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:select,text,number,color',
            'options' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $options = null;
            if (!empty($validated['options'])) {
                $options = collect(explode(',', $validated['options']))
                    ->map(fn($v) => trim($v))
                    ->filter()
                    ->values()
                    ->toArray();
            }

            $attribute = Attribute::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'options' => $options,
            ]);

            $category->attributes()->attach($attribute->id);

            DB::commit();

            return back()->with('success', 'Атрибут успешно добавлен!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, $categoryId, $attributeId)
    {
        $attr = Attribute::findOrFail($attributeId);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:select,text,number,color',
            'options' => 'nullable|string|max:500',
        ]);

        $options = null;
        if (!empty($data['options'])) {
            $options = collect(explode(',', $data['options']))
                ->map(fn($v) => trim($v))
                ->filter()
                ->values()
                ->toArray();
        }

        $attr->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'options' => $options,
        ]);

        return back()->with('success', 'Атрибут успешно обновлён!');
    }

    public function destroy(Category $category, Attribute $attribute)
    {
        try {
            DB::beginTransaction();

            $category->attributes()->detach($attribute->id);

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
