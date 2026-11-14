<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class AttributeHistory
{
    public static function detachFromCategory(int $categoryId, array $attributeIds): void
    {
        if ($attributeIds === []) {
            return;
        }
        DB::table('attribute_values')->whereNull('archived_at')->whereIn('attribute_id', $attributeIds)
            ->whereIn('product_id', DB::table('products')->select('id')->where('category_id', $categoryId))
            ->update(['archived_at' => now(), 'archive_reason' => 'category_definition_changed']);
    }

    public static function archiveIncompatible(int $productId, ?int $categoryId): void
    {
        DB::table('attribute_values')->where('product_id', $productId)->whereNull('archived_at')
            ->whereNotIn('attribute_id', DB::table('attribute_category')->select('attribute_id')->where('category_id', $categoryId))
            ->update(['archived_at' => now(), 'archive_reason' => 'product_category_changed']);
    }
}
