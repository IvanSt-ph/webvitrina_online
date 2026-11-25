<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryMenuController extends Controller
{
    public function __invoke(): JsonResponse
    {
        // One query for every depth. Inactive parents disconnect their entire branch.
        $all = Category::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'parent_id', 'name', 'slug', 'icon', 'image', 'sort_order']);
        $byParent = $all->groupBy('parent_id');

        foreach ($all as $category) {
            $category->setRelation('children', $byParent->get($category->id, new \Illuminate\Database\Eloquent\Collection()));
        }

        // The original menu sorts roots by sort_order; children have natural ID order.
        $categories = $all->whereNull('parent_id')->sortBy('sort_order')->values();

        return response()->json([
            'html' => view('categories.menu-tree', compact('categories'))->render(),
        ])->header('Cache-Control', 'private, no-store');
    }
}
