<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /** 📂 Список категорий */
    public function index()
    {
        $query = Category::with('parent');

        if (request()->filled('parent_id')) {
            $query->where('parent_id', request('parent_id'));
        }

        $categories = $query->orderBy('parent_id')->orderBy('name')->paginate(20);
        $parents    = Category::whereNull('parent_id')->orderBy('name')->get();

        return view('admin.categories.index', compact('categories', 'parents'));
    }

    /** 🆕 Создание */
    public function create()
    {
        $parents = Category::orderBy('name')->get();
        return view('admin.categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'slug'      => 'required|string|max:255|unique:categories',
            'icon'      => 'nullable|image',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('categories', 'public');
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория успешно создана.');
    }

    /** ✏️ Редактирование */
    public function edit(Category $category)
    {
        $parents = Category::where('id', '!=', $category->id)->orderBy('name')->get();
        return view('admin.categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'slug'      => 'required|string|max:255|unique:categories,slug,' . $category->id,
            'icon'      => 'nullable|image',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('categories', 'public');
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория обновлена.');
    }

    /** 🗑 Удаление */
    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория удалена.');
    }

    /** 📥 AJAX: получить подкатегории */
    public function children($id): JsonResponse
    {
        $children = Category::where('parent_id', $id)
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        return response()->json($children);
    }

    /** 📥 AJAX: получить родителя категории */
    public function parent($id): JsonResponse
    {
        $category = Category::select('id', 'parent_id')->findOrFail($id);
        return response()->json($category);
    }
}
