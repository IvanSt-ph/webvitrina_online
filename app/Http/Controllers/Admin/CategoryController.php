<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /** 📂 Главная страница категорий (AJAX + фильтры + пагинация) */
    public function index(Request $request)
{
    $query = Category::with('parent');

    // 🔍 Поиск по имени категории
    if ($request->filled('q')) {
        $query->where('name', 'like', '%' . $request->q . '%');
    }

    // 📂 Фильтр по родительской категории
    if ($request->filled('parent_id')) {
        $query->where('parent_id', $request->parent_id);
    }

    // ↕️ Сортировка
    $sort = $request->get('sort', 'name');
    $direction = $request->get('direction', 'asc');

    if (!in_array($sort, ['id', 'name', 'slug', 'parent_id'])) {
        $sort = 'name';
    }
    if (!in_array($direction, ['asc', 'desc'])) {
        $direction = 'asc';
    }

    $categories = $query->orderBy($sort, $direction)
        ->paginate(20)
        ->appends($request->query());

    $parents = Category::whereNull('parent_id')->orderBy('name')->get();


// 📊 ТОП-5 категорий по количеству подкатегорий и товаров
$topParents = Category::whereNull('parent_id')
    ->withCount(['children', 'products']) // 👈 добавлено
    ->orderByDesc('children_count')
    ->take(5)
    ->get(['id', 'name']);


    // 🔁 Если это AJAX-запрос — возвращаем только таблицу
    if ($request->ajax() || $request->boolean('ajax')) {
        return view('admin.categories.table', compact('categories'))->render();
    }

    // 📄 Обычная загрузка страницы
    return view('admin.categories.index', compact(
        'categories',
        'parents',
        'sort',
        'direction',
        'topParents' // ✅ добавляем в compact
    ));
}


    /** ➕ Форма создания */
    public function create()
    {
        $parents = Category::orderBy('parent_id')->orderBy('name')->get();
        return view('admin.categories.create', compact('parents'));
    }

    /** 💾 Создание категории */
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
        $parents = Category::whereNull('parent_id')
            ->orWhere('id', $category->parent_id)
            ->orderBy('name')->get();

        return view('admin.categories.edit', compact('category', 'parents'));
    }

    /** 💾 Обновление */
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
}
