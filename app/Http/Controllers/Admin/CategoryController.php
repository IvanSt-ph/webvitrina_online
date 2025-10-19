<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * 📂 Главная страница категорий (AJAX + фильтры + аналитика)
     */
    public function index(Request $request)
    {
        // ⚙️ Основной запрос
        $query = Category::with('parent');

        // 🔍 Поиск по названию
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

        if (!in_array($sort, ['id', 'name', 'slug', 'parent_id'])) $sort = 'name';
        if (!in_array($direction, ['asc', 'desc'])) $direction = 'asc';

        $categories = $query->orderBy($sort, $direction)
            ->paginate(20)
            ->appends($request->query());

        $parents = Category::whereNull('parent_id')->orderBy('name')->get();

        // ⚙️ Режим анализа (scale / fill / density)
        $mode = $request->get('mode', 'scale');


        

// 📊 ТОП-5 категорий по выбранному критерию
$mode = $request->get('mode', 'products'); // по умолчанию сортировка по товарам

$topParents = Category::whereNull('parent_id')
    ->with(['children'])
    ->withCount(['children'])
    ->get(['id', 'name', 'icon'])
    ->map(function ($cat) {
        // Собираем ID всех подкатегорий (включая саму категорию)
        $allIds = $cat->allChildrenIds();

        // Подсчитываем товары
        $cat->products_count = \App\Models\Product::whereIn('category_id', $allIds)->count();

        return $cat;
    })
    ->map(function ($cat) use ($mode) {
        // Определяем "рейтинг" категории в зависимости от выбранного режима
        switch ($mode) {
            case 'subcats': // 🧱 по подкатегориям
                $cat->score = $cat->children_count;
                break;

            case 'efficiency': // ⚖️ товары / подкатегории
                $cat->score = round($cat->products_count / max($cat->children_count, 1), 2);
                break;

            default: // 🛍 по товарам
                $cat->score = $cat->products_count;
        }
        return $cat;
    })
    ->sortByDesc('score')
    ->take(5);



        // 🔁 Если AJAX — вернуть только таблицу
        if ($request->ajax() || $request->boolean('ajax')) {
            return view('admin.categories.table', compact('categories'))->render();
        }

        // 📄 Обычная загрузка страницы
        return view('admin.categories.index', compact(
            'categories',
            'parents',
            'sort',
            'direction',
            'topParents',
            'mode' // ✅ теперь передаём в шаблон
        ));
    }

    /** ➕ Создание категории */
    public function create()
    {
        $parents = Category::orderBy('parent_id')->orderBy('name')->get();
        return view('admin.categories.create', compact('parents'));
    }

    /** 💾 Сохранение новой категории */
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

        if ($request->hasFile('image')) {
    $data['image'] = $request->file('image')->storeAs(
    'categories',
    uniqid().'.'.$request->file('image')->getClientOriginalExtension(),
    'public'
);

}


        Category::create($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория успешно создана.');
    }

    /** ✏️ Редактирование категории */
    public function edit(Category $category)
    {
        $parents = Category::whereNull('parent_id')
            ->orWhere('id', $category->parent_id)
            ->orderBy('name')->get();

        return view('admin.categories.edit', compact('category', 'parents'));
    }


/** 💾 Обновление категории */
public function update(Request $request, Category $category)
{
    $data = $request->validate([
        'name'      => 'required|string|max:255',
        'slug'      => 'required|string|max:255|unique:categories,slug,' . $category->id,
        'icon'      => 'nullable|image',
        'image'     => 'nullable|image',
        'parent_id' => 'nullable|exists:categories,id',
    ]);

// 🖼️ Иконка (для меню)
if ($request->hasFile('icon') && $request->file('icon')->isValid()) {
    $data['icon'] = $request->file('icon')->storeAs(
        'categories',
        uniqid().'.'.$request->file('icon')->getClientOriginalExtension(),
        'public'
    );
}

// 🖼️ Изображение (для плитки)
if ($request->hasFile('image') && $request->file('image')->isValid()) {
    $data['image'] = $request->file('image')->storeAs(
        'categories',
        uniqid().'.'.$request->file('image')->getClientOriginalExtension(),
        'public'
    );
}


    // 💾 Обновление категории
    $category->update($data);

    return redirect()->route('admin.categories.index')
        ->with('success', 'Категория успешно обновлена.');
}









    /** 🗑 Удаление категории */
    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория удалена.');
    }

    /** 📥 AJAX: Получить подкатегории */
    public function children($id): JsonResponse
    {
        $children = Category::where('parent_id', $id)
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        return response()->json($children);
    }
}
