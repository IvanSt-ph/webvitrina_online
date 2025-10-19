<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class CategoryController extends Controller
{
    /** 📂 Главная страница категорий */
    public function index(Request $request)
    {
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

        $mode = $request->get('mode', 'products');
        $topParents = Category::whereNull('parent_id')
            ->with(['children'])
            ->withCount(['children'])
            ->get(['id', 'name', 'icon'])
            ->map(function ($cat) {
                $allIds = $cat->allChildrenIds();
                $cat->products_count = \App\Models\Product::whereIn('category_id', $allIds)->count();
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

        if ($request->ajax() || $request->boolean('ajax')) {
            return view('admin.categories.table', compact('categories'))->render();
        }

        return view('admin.categories.index', compact(
            'categories',
            'parents',
            'sort',
            'direction',
            'topParents',
            'mode'
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
        $parents = Category::whereNull('parent_id')
            ->orWhere('id', $category->parent_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.categories.edit', compact('category', 'parents'));
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

    /** 📥 AJAX: Получить подкатегории */
    public function children($id): JsonResponse
    {
        $children = Category::where('parent_id', $id)
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        return response()->json($children);
    }
}
