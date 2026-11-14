<?php

namespace App\Services\AttributeImport;

use App\Models\Attribute;
use App\Models\Category;
use App\Services\AttributeDefinitionService;
use App\Services\AttributeImport\ImportException as RuntimeException;
use App\Services\CategoryFilterCacheService;
use App\Services\ReviewRatingCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ImportService
{
    private function signature(array $data, array $colors = []): string
    {
        return json_encode([$data['name'], $data['type'], $data['unit'], (bool) $data['is_filterable'], $data['options'], $colors], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function colorIds(Attribute $attribute): array
    {
        return $attribute->colors->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
    }

    private function nameKey(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)));
    }

    public function preview(array $input, bool $lock = false): array
    {
        $cq = Category::orderBy('id');
        $aq = Attribute::with('colors')->orderBy('id');
        $lq = DB::table('attribute_category')->orderBy('id');
        $colorsQuery = DB::table('colors')->orderBy('id');
        $paletteQuery = DB::table('attribute_color')->orderBy('id');
        if ($lock) {
            $cq->lockForUpdate();
            $aq->lockForUpdate();
            $lq->lockForUpdate();
            $colorsQuery->lockForUpdate();
            $paletteQuery->lockForUpdate();
        }
        $catalog = $colorsQuery->get()->map(fn ($row) => (array) $row)->all();
        $paletteLinks = $paletteQuery->get()->toArray();
        $categories = $cq->get();
        $attributes = $aq->get();
        $links = $lq->get();
        $bySlug = $categories->groupBy('slug');
        $parents = $categories->pluck('parent_id')->filter()->all();
        $byName = $attributes->groupBy(fn ($a) => $this->nameKey($a->name));
        $rows = [];
        $seen = [];
        foreach ($input as $number => $raw) {
            $errors = [];
            $type = $raw['type'];
            $colorIds = [];
            try {
                ColorPalette::assertType($raw['attribute_name'], $type);
                if ($type === 'color') {
                    if ($raw['unit'] !== '' || $raw['options'] !== '') {
                        throw new RuntimeException('color: unit и options должны быть пустыми.');
                    }
                    $colorIds = ColorPalette::resolve($raw['colors'] ?? '', $catalog);
                    ColorPalette::assertSelection($raw['attribute_name'], $type, $colorIds, $catalog);
                } elseif (($raw['colors'] ?? '') !== '') {
                    throw new RuntimeException('colors допускается только для color.');
                }
            } catch (RuntimeException $exception) {
                $errors[] = $exception->getMessage();
            }
            foreach (['category_slug' => 255, 'attribute_name' => 255, 'group_name' => 255, 'unit' => 50] as $field => $max) {
                if (mb_strlen($raw[$field]) > $max || preg_match('/[\x00-\x1f\x7f]/u', $raw[$field]) || $raw[$field] !== trim($raw[$field])) {
                    $errors[] = "{$field}: превышена длина, пробелы по краям или управляющие символы.";
                }
            }
            foreach (['category_slug', 'attribute_name', 'type', 'is_filterable', 'sort_order'] as $field) {
                if ($raw[$field] === '') {
                    $errors[] = "{$field}: обязательное значение.";
                }
            }
            if (! in_array($raw['is_filterable'], ['0', '1'], true)) {
                $errors[] = 'is_filterable: допустимы только 0 и 1.';
            }
            if (! preg_match('/^(0|[1-9][0-9]{0,9})$/D', $raw['sort_order']) || (float) $raw['sort_order'] > 4294967295) {
                $errors[] = 'sort_order: требуется целое число от 0 до 4294967295.';
            }
            $matches = $bySlug->get($raw['category_slug'], collect());
            $category = $matches->count() === 1 ? $matches->first() : null;
            if (! $category) {
                $errors[] = 'Категория не найдена или slug неоднозначен.';
            } elseif (in_array($category->id, $parents)) {
                $errors[] = 'Разрешены только конечные категории.';
            }
            $options = null;
            if ($type === 'select') {
                try {
                    $options = json_decode($raw['options'], true, 16, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    $errors[] = 'options: требуется JSON-массив строк.';
                }
                if (! is_array($options) || ! array_is_list($options) || count($options) < 1 || count($options) > 100) {
                    $errors[] = 'options: требуется от 1 до 100 вариантов.';
                } else {
                    $keys = [];
                    foreach ($options as $option) {
                        if (! is_string($option) || $option === '' || mb_strlen($option) > 255 || trim($option) !== $option || preg_match('/[\x00-\x1f\x7f]/u', $option)) {
                            $errors[] = 'options: варианты должны быть непустыми строками до 255 символов без лишних пробелов.';
                            break;
                        }
                        $key = $this->nameKey($option);
                        if (isset($keys[$key])) {
                            $errors[] = 'options: повторяющиеся или неоднозначные варианты.';
                            break;
                        }
                        $keys[$key] = true;
                    }
                }
            } elseif ($raw['options'] !== '') {
                $errors[] = 'options допускается только для select.';
            }
            $data = ['name' => $raw['attribute_name'], 'type' => $type, 'unit' => $raw['unit'] === '' ? null : $raw['unit'], 'is_filterable' => $raw['is_filterable'] === '1', 'options' => $options];
            $signature = $this->signature($data, $colorIds);
            $key = $this->nameKey($data['name']);
            $status = $errors ? 'INVALID' : (in_array($type, ['text', 'number', 'select', 'color'], true) ? 'CREATE_AND_LINK' : 'UNSUPPORTED');
            if (! in_array($type, ['text', 'number', 'select', 'color'], true)) {
                $errors[] = 'Тип не поддерживается: '.$type.'. Требуется ручная проверка.';
            }
            $attributeId = null;
            if ($status === 'CREATE_AND_LINK') {
                $candidates = $byName->get($key, collect());
                $attached = $links->where('category_id', $category->id)->pluck('attribute_id')->all();
                $exactMatches = $candidates->filter(fn ($a) => $this->signature($a->toArray(), $this->colorIds($a)) === $signature);
                $exact = $exactMatches->first(fn ($a) => in_array($a->id, $attached)) ?? $exactMatches->first();
                $attachedCandidates = $candidates->filter(fn ($a) => in_array($a->id, $attached));
                $differentAttached = $attachedCandidates->contains(fn ($a) => $this->signature($a->toArray(), $this->colorIds($a)) !== $signature);
                // Names are category-local; only full definitions are reused globally.
                if ($attachedCandidates->count() > 1 || $differentAttached) {
                    $status = 'CONFLICT';
                    if ($attachedCandidates->count() > 1) {
                        $errors[] = 'В категории привязано несколько определений с одинаковым нормализованным названием.';
                    }
                    foreach ($attachedCandidates->take(5) as $candidate) {
                        $differences = [];
                        foreach (['name', 'type', 'unit', 'is_filterable', 'options'] as $field) {
                            $existing = $field === 'is_filterable' ? (bool) $candidate->$field : $candidate->$field;
                            if ($existing !== $data[$field]) {
                                $differences[] = $field.': существует '.json_encode($existing, JSON_UNESCAPED_UNICODE).', в файле '.json_encode($data[$field], JSON_UNESCAPED_UNICODE);
                            }
                        }
                        if ($this->colorIds($candidate) !== $colorIds) {
                            $differences[] = 'набор цветов отличается';
                        }
                        if ($differences) {
                            $errors[] = 'Определение #'.$candidate->id.' отличается: '.implode('; ', $differences).'.';
                        }
                    }
                    if (! $errors) {
                        $errors[] = 'Существуют конфликтующие определения похожего названия.';
                    }
                } elseif ($exact) {
                    $attributeId = $exact->id;
                    $status = in_array($attributeId, $attached) ? 'UNCHANGED' : 'ADD_LINK';
                }
            }
            $rowKey = $raw['category_slug'].'|'.$key;
            if (isset($seen[$rowKey])) {
                $previous = $seen[$rowKey];
                $status = 'INVALID';
                $errors[] = "Повтор характеристики в категории; первая строка {$previous}.";
                $rows[$previous]['status'] = 'INVALID';
                $rows[$previous]['errors'][] = "Повтор в строке {$number}.";
            }
            $seen[$rowKey] = $number;
            $rows[$number] = ['line' => $number, 'raw' => $raw, 'data' => $data, 'color_ids' => $colorIds, 'category_id' => $category?->id, 'attribute_id' => $attributeId, 'status' => $status, 'errors' => $errors];
        }
        $counts = array_fill_keys(['UNCHANGED', 'ADD_LINK', 'CREATE_AND_LINK', 'CONFLICT', 'INVALID', 'UNSUPPORTED'], 0);
        $new = [];
        foreach ($rows as $row) {
            $counts[$row['status']]++;
            if ($row['status'] === 'CREATE_AND_LINK') {
                $new[$this->signature($row['data'], $row['color_ids'])] = true;
            }
        }
        $report = ['rows' => array_values($rows), 'counts' => $counts, 'read' => count($rows), 'valid' => $counts['UNCHANGED'] + $counts['ADD_LINK'] + $counts['CREATE_AND_LINK'], 'exact' => $counts['UNCHANGED'] + $counts['ADD_LINK'], 'new_definitions' => count($new), 'new_links' => $counts['ADD_LINK'] + $counts['CREATE_AND_LINK'], 'blocked' => ! $rows || $counts['CONFLICT'] + $counts['INVALID'] + $counts['UNSUPPORTED'] > 0];
        // Conservative snapshot: any catalog definition/link/category change requires review again.
        $report['fingerprint'] = hash('sha256', json_encode([$report, $categories->toArray(), $attributes->toArray(), $links->toArray(), $catalog, $paletteLinks], JSON_THROW_ON_ERROR));

        return $report;
    }

    public function apply(array $input, string $fingerprint): array
    {
        $databaseGuard = app(ImportDatabaseGuard::class);
        $databaseGuard->assertAllowed();
        $warnings = [];
        $result = DB::transaction(function () use ($input, $fingerprint, &$warnings, $databaseGuard) {
            // Recheck the transaction's write connection before locks and inserts.
            $databaseGuard->assertAllowed();
            AttributeDefinitionService::lock();
            $report = $this->preview($input, true);
            if ($report['blocked'] || ! hash_equals($fingerprint, $report['fingerprint'])) {
                throw new RuntimeException('План импорта изменился или содержит блокирующие строки. Выполните предпросмотр и подтвердите заново.');
            }
            $affected = [];
            foreach ($report['rows'] as $row) {
                if ($row['status'] === 'UNCHANGED') {
                    continue;
                }
                $attribute = AttributeDefinitionService::findOrCreate($row['data'], $row['color_ids']);
                DB::table('attribute_category')->insert(['category_id' => $row['category_id'], 'attribute_id' => $attribute->id, 'sort_order' => (int) $row['raw']['sort_order']]);
                $affected[$row['category_id']] = true;
            }
            DB::afterCommit(function () use ($affected, $report, &$warnings): void {
                foreach (array_keys($affected) as $id) {
                    try {
                        CategoryFilterCacheService::clearForCategoryAndAncestors(Category::findOrFail($id));
                    } catch (\Throwable) {
                        $warnings[] = 'Данные сохранены, но не удалось очистить кеш фильтров. Требуется повторная очистка администратором.';
                    }
                }
                try {
                    if ($affected) {
                        ReviewRatingCache::invalidate();
                    }
                } catch (\Throwable) {
                    $warnings[] = 'Данные сохранены, но обновление кеша каталога не завершено.';
                }
                try {
                    Log::info('Category attribute import committed', ['rows' => $report['read'], 'new_definitions' => $report['new_definitions'], 'new_links' => $report['new_links'], 'cache_warnings' => count($warnings)]);
                } catch (\Throwable) {
                    $warnings[] = 'Данные сохранены, но журналирование результата недоступно.';
                }
            });

            return $report;
        });
        $result['warnings'] = array_values(array_unique($warnings));

        return $result;
    }
}
