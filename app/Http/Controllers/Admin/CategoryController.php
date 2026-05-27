<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        if ($request->ajax() || $request->boolean('ajax')) {
            return response()->json([
                'desktop' => view('admin.categories.table', compact('categories'))->render(),
                'mobile' => view('admin.categories.mobile-list', compact('categories'))->render(),
                'pagination' => view('admin.categories.pagination', compact('categories'))->render(),
            ]);
        }

        $parents = Category::whereNull('parent_id')->orderBy('name')->get();

        // ====== 🔹 Режим аналитики ======
        $mode = $request->get('mode', 'products');

        $dates = collect(range(6, 0))
            ->map(fn ($i) => Carbon::today()->subDays($i)->toDateString());

        $rootCategories = Category::whereNull('parent_id')
            ->with(['children'])
            ->withCount(['children'])
            ->get(['id', 'name', 'icon']);

        $categoryRootMap = collect();
        $rootCategoryIds = [];

        foreach ($rootCategories as $cat) {
            foreach ($cat->allChildrenIds() as $categoryId) {
                $categoryRootMap->put((int) $categoryId, $cat->id);
                $rootCategoryIds[$cat->id][] = (int) $categoryId;
            }
        }

        $allCategoryIds = $categoryRootMap->keys();

        $productCounts = Product::whereIn('category_id', $allCategoryIds)
            ->select('category_id', DB::raw('COUNT(*) as total'))
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $dailyCounts = Product::whereIn('category_id', $allCategoryIds)
            ->where('created_at', '>=', Carbon::parse($dates->first())->startOfDay())
            ->select('category_id', DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('category_id', DB::raw('DATE(created_at)'))
            ->get();

        $chartByRoot = [];
        foreach ($rootCategories as $cat) {
            $chartByRoot[$cat->id] = $dates->mapWithKeys(fn ($date) => [$date => 0])->all();
        }

        foreach ($dailyCounts as $row) {
            $rootId = $categoryRootMap->get((int) $row->category_id);
            if ($rootId && isset($chartByRoot[$rootId][$row->date])) {
                $chartByRoot[$rootId][$row->date] += (int) $row->total;
            }
        }

        // ====== 🔹 Топ 5 родительских категорий ======
        $topParents = $rootCategories
            ->map(function ($cat) use ($productCounts, $rootCategoryIds, $chartByRoot) {
                $cat->products_count = collect($rootCategoryIds[$cat->id] ?? [])
                    ->sum(fn ($categoryId) => (int) ($productCounts[$categoryId] ?? 0));

                $cat->chart_data = collect(array_values($chartByRoot[$cat->id] ?? []));
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
            'icon'      => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'image'     => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        if ($request->hasFile('icon')) {
            $data['icon'] = $this->storeCategoryImage($request->file('icon'), 'categories/icons', 256, null, 82);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeCategoryImage($request->file('image'), 'categories/thumbs', 600, 600, 82);
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
            'icon'      => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'image'     => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $this->validateParentChange($category, $data['parent_id'] ?? null);

        if ($request->hasFile('icon')) {
            $oldIcon = $category->icon;
            $data['icon'] = $this->storeCategoryImage($request->file('icon'), 'categories/icons', 256, null, 82);
            $this->deleteCategoryImage($oldIcon);
        }

        if ($request->hasFile('image')) {
            $oldImage = $category->image;
            $data['image'] = $this->storeCategoryImage($request->file('image'), 'categories/thumbs', 600, 600, 82);
            $this->deleteCategoryImage($oldImage);
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория успешно обновлена.');
    }

    /** 🗑 Удаление категории */
    public function destroy(Category $category)
    {
        Category::whereIn('id', $category->allChildrenIds())
            ->get(['icon', 'image'])
            ->each(function (Category $category): void {
                $this->deleteCategoryImage($category->icon);
                $this->deleteCategoryImage($category->image);
            });

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


    /** 🌳 Получить цепочку категорий от корня до текущей */
public function chain($id): JsonResponse
{
    $chain = collect();
    $category = Category::select('id', 'name', 'parent_id')
        ->with('parent:id,name,parent_id')
        ->find($id);

    while ($category) {
        $chain->prepend([
            'id'   => $category->id,
            'name' => $category->name,
        ]);
        $category = $category->parent;
    }

    return response()->json($chain->values());
}

    private function storeCategoryImage(
        \Illuminate\Http\UploadedFile $file,
        string $directory,
        int $width,
        ?int $height = null,
        int $quality = 82
    ): string {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());

        $image = $height
            ? $image->coverDown(width: $width, height: $height)
            : $image->scaleDown(width: $width);

        $path = trim($directory, '/') . '/' . Str::uuid() . '.webp';

        Storage::disk('public')->put($path, $image->toWebp($quality)->toString());

        return $path;
    }

    private function deleteCategoryImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function validateParentChange(Category $category, ?int $parentId): void
    {
        if (!$parentId) {
            return;
        }

        if ($parentId === $category->id || $category->allChildrenIds()->contains($parentId)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parent_id' => 'Категорию нельзя вложить в саму себя или в свою подкатегорию.',
            ]);
        }
    }

}
