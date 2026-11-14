<?php

namespace App\Services;

/** Pure read-only planner. No automatic remapping by name and no deletion API. */
final class AttributeDuplicateAudit
{
    public static function identity(array $attribute): string
    {
        $options = $attribute['options'] ?? null;
        if (is_string($options)) {
            $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);
        }

        // Array order is meaningful for the UI; null and [] remain distinct.
        return json_encode([$attribute['name'], $attribute['type'], $attribute['unit'] ?? null, $options], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function report(array $attributes, array $links, array $values, array $colors = []): array
    {
        $byId = $groups = $categoryUse = $productUse = $palette = [];
        foreach ($attributes as $attribute) {
            $id = (int) $attribute['id'];
            $byId[$id] = $attribute;
            $groups[self::identity($attribute)][] = $id;
        }
        foreach ($links as $link) {
            $categoryUse[(int) $link['attribute_id']][(int) $link['category_id']] = $link['sort_order'] ?? 0;
        }
        foreach ($values as $value) {
            $productUse[(int) $value['attribute_id']][] = $value;
        }
        foreach ($colors as $color) {
            $palette[(int) $color['attribute_id']][] = (int) $color['color_id'];
        }
        foreach ($palette as &$ids) {
            $ids = array_values(array_unique($ids));
            sort($ids);
        }
        unset($ids);
        $duplicates = [];
        $candidateCount = 0;
        foreach ($groups as $identity => $ids) {
            if (count($ids) < 2) {
                continue;
            }
            // Prefer an actively linked identity, then a used identity, then oldest ID.
            usort($ids, fn ($a, $b) => [-(int) isset($categoryUse[$a]), -(int) isset($productUse[$a]), $a]
                <=> [-(int) isset($categoryUse[$b]), -(int) isset($productUse[$b]), $b]);
            $canonical = $ids[0];
            $conflicts = $seenProducts = $seenCategories = [];
            foreach ($ids as $id) {
                if (($palette[$id] ?? []) !== ($palette[$canonical] ?? [])) {
                    $conflicts[] = 'different_color_links';
                }
                if ((int) $byId[$id]['is_filterable'] !== (int) $byId[$canonical]['is_filterable']) {
                    $conflicts[] = 'different_filterability';
                }
                foreach ($productUse[$id] ?? [] as $value) {
                    // Even equal values must not be collapsed without reviewing row history.
                    if (isset($seenProducts[$value['product_id']])) {
                        $conflicts[] = 'overlapping_product_values';
                    }
                    $seenProducts[$value['product_id']] = true;
                }
                foreach ($categoryUse[$id] ?? [] as $category => $order) {
                    if (isset($seenCategories[$category]) && $seenCategories[$category] !== (int) $order) {
                        $conflicts[] = 'different_sort_order';
                    }
                    $seenCategories[$category] = (int) $order;
                }
            }
            $safe = $conflicts === [];
            if ($safe) {
                $candidateCount += count($ids) - 1;
            }
            $duplicates[] = ['identity' => json_decode($identity, true), 'canonical_id' => $canonical,
                'members' => array_map(fn ($id) => ['id' => $id, 'category_count' => count($categoryUse[$id] ?? []), 'value_count' => count($productUse[$id] ?? [])], $ids),
                'candidate_ids' => $safe ? array_slice($ids, 1) : [], 'conflicts' => array_values(array_unique($conflicts))];
        }
        $historical = [];
        foreach ($values as $value) {
            $id = (int) $value['attribute_id'];
            if (empty($value['archived_at']) && isset($categoryUse[$id][(int) $value['category_id']])) {
                continue;
            }
            $exact = $sameName = [];
            foreach ($byId as $candidateId => $attribute) {
                if (! isset($categoryUse[$candidateId][(int) $value['category_id']]) || ! isset($byId[$id])) {
                    continue;
                }
                if (self::identity($attribute) === self::identity($byId[$id])) {
                    $exact[] = $candidateId;
                } elseif ($attribute['name'] === $byId[$id]['name']) {
                    $sameName[] = $candidateId;
                }
            }
            $historical[] = ['value_id' => (int) $value['id'], 'product_id' => (int) $value['product_id'],
                'attribute_id' => $id, 'name' => $byId[$id]['name'] ?? null, 'category_id' => $value['category_id'],
                'exact_candidates' => $exact, 'same_name_different_definition' => $sameName];
        }

        return ['duplicate_groups' => $duplicates, 'candidate_attribute_removals_after_review' => $candidateCount,
            'unused_attribute_ids' => array_values(array_filter(array_keys($byId), fn ($id) => ! isset($categoryUse[$id]) && ! isset($productUse[$id]))),
            'historical_values' => $historical,
            'orphan_value_count' => count(array_filter($values, fn ($v) => ! isset($byId[$v['attribute_id']]))),
            'policy' => 'Dry-run only. Preserve every value row and its ID; review conflicts, FK constraints and concurrent writes before applying a merge. Unused does not mean duplicate.'];
    }
}
