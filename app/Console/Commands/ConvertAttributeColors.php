<?php

namespace App\Console\Commands;

use App\Services\AttributeColorConversion;
use App\Services\CategoryFilterCacheService;
use App\Services\ReviewRatingCache;
use App\Models\Category;
use Illuminate\Console\Command;

final class ConvertAttributeColors extends Command
{
    protected $signature = 'attributes:convert-colors {--database= : Must explicitly be webv3_attributes_test} {--apply= : Fingerprint from reviewed preview}';

    protected $description = 'Preview or explicitly apply color conversion, preserving IDs and history';

    public function handle(AttributeColorConversion $conversion): int
    {
        if ($this->option('database') !== 'webv3_attributes_test') {
            $this->error('Specify --database=webv3_attributes_test; the active connection must already use that database.');

            return self::FAILURE;
        }
        if (! $this->option('apply')) {
            $this->line(json_encode($conversion->preview(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }
        app(\App\Services\AttributeImport\ImportDatabaseGuard::class)->assertAllowed();
        $result = $conversion->apply((string) $this->option('apply'));
        $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        if ($result['changed']) {
            try {
                // All category filters may contain shared definitions or descendants.
                foreach (Category::all() as $category) {
                    CategoryFilterCacheService::clearForCategoryAndAncestors($category);
                }
                ReviewRatingCache::invalidate();
            } catch (\Throwable) {
                $this->error('Преобразование сохранено. Очистка кеша не завершена; требуется отдельная очистка. Не считать это откатом данных.');

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
