<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AttributeDuplicateCleanup
{
    public function snapshot(): array
    {
        $attributes = DB::table('attributes')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $links = DB::table('attribute_category')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $values = DB::table('attribute_values as av')->leftJoin('products as p', 'p.id', '=', 'av.product_id')
            ->select('av.*', 'p.category_id')->orderBy('av.id')->get()->map(fn ($row) => (array) $row)->all();
        $colors = DB::table('attribute_color')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();

        return ['fingerprint' => hash('sha256', json_encode([$attributes, $links, $values, $colors], JSON_THROW_ON_ERROR)),
            'report' => (new AttributeDuplicateAudit)->report($attributes, $links, $values, $colors)];
    }

    /** Explicit reviewed application is intentionally forbidden on production databases. */
    public function apply(string $expectedFingerprint): int
    {
        $database = DB::selectOne('SELECT DATABASE() AS name')->name;
        if (! in_array($database, ['webv3_testing', 'webv3_attributes_test'], true)) {
            throw new RuntimeException('Cleanup application is restricted to explicit test databases.');
        }

        return DB::transaction(function () use ($expectedFingerprint): int {
            AttributeDefinitionService::lock();
            // This is an offline maintenance operation. Lock products before value rows,
            // matching the product editor; FK parent locks prevent new referencing rows.
            DB::table('products')->orderBy('id')->lockForUpdate()->get(['id']);
            foreach (['attributes', 'attribute_category', 'attribute_values', 'attribute_color'] as $table) {
                DB::table($table)->orderBy('id')->lockForUpdate()->get();
            }
            $snapshot = $this->snapshot();
            if (! hash_equals($snapshot['fingerprint'], $expectedFingerprint)) {
                throw new RuntimeException('Data changed since dry-run. Review a new report.');
            }
            $removed = 0;
            foreach ($snapshot['report']['duplicate_groups'] as $group) {
                $canonical = $group['canonical_id'];
                if ($group['candidate_ids'] === []) {
                    continue;
                }
                DB::table('attribute_values')->whereIn('attribute_id', [$canonical, ...$group['candidate_ids']])->whereNull('archived_at')
                    ->whereNotExists(function ($query) {
                        $query->selectRaw('1')->from('products')->join('attribute_category', 'attribute_category.category_id', '=', 'products.category_id')
                            ->whereColumn('products.id', 'attribute_values.product_id')
                            ->whereColumn('attribute_category.attribute_id', 'attribute_values.attribute_id');
                    })->update(['archived_at' => now(), 'archive_reason' => 'duplicate_merge_history']);
                foreach ($group['candidate_ids'] as $duplicate) {
                    // A formerly detached row must never become active through ID remapping.
                    DB::table('attribute_values')->where('attribute_id', $duplicate)->whereNull('archived_at')
                        ->whereNotExists(function ($query) {
                            $query->selectRaw('1')->from('products')->join('attribute_category', 'attribute_category.category_id', '=', 'products.category_id')
                                ->whereColumn('products.id', 'attribute_values.product_id')
                                ->whereColumn('attribute_category.attribute_id', 'attribute_values.attribute_id');
                        })->update(['archived_at' => now(), 'archive_reason' => 'duplicate_merge_history']);
                    DB::table('attribute_values')->where('attribute_id', $duplicate)->update(['attribute_id' => $canonical]);
                    foreach (DB::table('attribute_category')->where('attribute_id', $duplicate)->get() as $link) {
                        $exists = DB::table('attribute_category')->where('attribute_id', $canonical)->where('category_id', $link->category_id)->exists();
                        if ($exists) {
                            DB::table('attribute_category')->where('id', $link->id)->delete();
                        } else {
                            DB::table('attribute_category')->where('id', $link->id)->update(['attribute_id' => $canonical]);
                        }
                    }
                    // Palette equality was checked; canonical color links already exist.
                    DB::table('attribute_color')->where('attribute_id', $duplicate)->delete();
                    DB::table('attributes')->where('id', $duplicate)->delete();
                    $removed++;
                }
            }
            DB::afterCommit(function (): void {
                foreach (Category::all() as $category) {
                    CategoryFilterCacheService::clearFor($category);
                }
                ReviewRatingCache::invalidate();
            });

            return $removed;
        });
    }
}
