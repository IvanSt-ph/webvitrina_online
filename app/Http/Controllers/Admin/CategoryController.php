<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Carbon\Carbon;

class CategoryController extends Controller
{
    /** 📂 Главная страница категорий */
    public function index(Request $request)
    {
        // ====== 🔹 Базовый запрос ======
        $query = Category::with('parent');

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');

        if (!in_array($sort, ['id', 'name', 'slug', 'parent_id'])) $sort = 'name';
        if (!in_array($direction, ['asc', 'desc'])) $direction = 'asc';

        $categories = $query->orderBy($sort, $direction)
            ->paginate(20)
            ->appends($request->query());

        $parents = Category::whereNull('parent_id')->orderBy('name')->get();

        // ====== 🔹 Режим аналитики ======
        $mode = $request->get('mode', 'products');

        // ====== 🔹 Топ 5 родительских категорий ======
        $topParents = Category::whereNull('parent_id')
            ->with(['children'])
            ->withCount(['children'])
            ->get(['id', 'name', 'icon'])
            ->map(function ($cat) {
                $allIds = $cat->allChildrenIds();

                // Количество товаров
                $cat->products_count = Product::whereIn('category_id', $allIds)->count();

                // === Реальная динамика добавления товаров за 7 дней ===
                $dates = collect(range(6, 0))
    ->map(fn($i) => Carbon::today()->subDays($i)->toDateString());

$cat->chart_data = $dates->map(fn($date) =>
    Product::whereIn('category_id', $allIds)
        ->whereDate('created_at', $date)
        ->count()
);


                return $cat;
            })
            ->map(function ($cat) use ($mode) {
                switch ($mode) {
                    case 'subcats':
                        $cat->score = $cat->children_count;
                        break;
                    case 'efficiency':
                        $cat->score = round($cat->products_count / max($cat->children_count, 1), 2);
                        break;
                    default:
                        $cat->score = $cat->products_count;
                }
                return $cat;
            })
            ->sortByDesc('score')
            ->take(5);

        // ====== 🔹 Общая статистика ======
        $stats = [
            'total' => Category::count(),
            'roots' => Category::whereNull('parent_id')->count(),
        ];
        $stats['subs'] = max($stats['total'] - $stats['roots'], 0);

        // ====== 🔹 AJAX подгрузка таблицы ======
        if ($request->ajax() || $request->boolean('ajax')) {
            return view('admin.categories.table', compact('categories'))->render();
        }

        // ====== 🔹 Итоговый вывод ======
        return view('admin.categories.index', compact(
            'categories',
            'parents',
            'sort',
            'direction',
            'topParents',
            'mode',
            'stats'
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
            'icon'      => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'image'     => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('categories/icons', 'public');
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('categories/original', 'public');
            $thumbPath = 'categories/thumbs/' . basename($path);

            $manager = new ImageManager(new Driver());
            $img = $manager->read($file->getRealPath())
                ->scale(width: 300)
                ->toWebp(80);

            Storage::disk('public')->put($thumbPath, $img->toString());
            $data['image'] = $thumbPath;
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория успешно создана.');
    }

    /** ✏️ Редактирование категории */
    public function edit(Category $category)
    {
        $chain = collect();
        $current = $category->parent;
        while ($current) {
            $chain->prepend($current);
            $current = $current->parent;
        }

        $parents = Category::orderBy('parent_id')->orderBy('name')->get(['id', 'name', 'parent_id']);

        return view('admin.categories.edit', compact('category', 'parents', 'chain'));
    }

    /** 💾 Обновление категории */
    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'slug'      => 'required|string|max:255|unique:categories,slug,' . $category->id,
            'icon'      => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'image'     => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('categories/icons', 'public');
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('categories/original', 'public');
            $thumbPath = 'categories/thumbs/' . basename($path);

            $manager = new ImageManager(new Driver());
            $img = $manager->read($file->getRealPath())
                ->scale(width: 300)
                ->toWebp(80);

            Storage::disk('public')->put($thumbPath, $img->toString());
            $data['image'] = $thumbPath;
        }

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

    /** 📁 Получить корневые категории */
    public function root(): JsonResponse
    {
        $roots = Category::whereNull('parent_id')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($roots);
    }

    /** 📂 Получить подкатегории по ID родителя */
    public function children($id): JsonResponse
    {
        $children = \Cache::remember("categories_children_$id", 600, function () use ($id) {
            return Category::where('parent_id', $id)
                ->select('id', 'name', 'parent_id')
                ->orderBy('name')
                ->get();
        });

        return response()->json($children);
    }

    /** 🧭 Получить родителя категории */
    public function parent($id): JsonResponse
    {
        $category = Category::find($id, ['id', 'parent_id']);
        return response()->json($category);
    }
}
