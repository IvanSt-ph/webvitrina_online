<?php

namespace App\Services;

use App\Services\AttributeImport\ColorConversionPlan;
use App\Services\AttributeImport\ImportException;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

final class AttributeColorConversion
{
    private function connection(): Connection
    {
        $db = DB::connection();
        if ($db->getDriverName() !== 'mysql' || $db->getTablePrefix() !== ''
            || $db->selectOne('SELECT DATABASE() AS name', [], false)?->name !== 'webv3_attributes_test') {
            throw new ImportException('Преобразование разрешено исключительно в webv3_attributes_test.');
        }

        return $db;
    }

    public function preview(): array
    {
        return $this->snapshot($this->connection(), false);
    }

    private function snapshot(Connection $db, bool $lock): array
    {
        $read = function ($query) use ($lock): array {
            $query->useWritePdo();
            if ($lock) {
                $query->lockForUpdate();
            }

            return $query->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        };
        // Same definition lock as other writers; category locks also serialize seller saves.
        $categories = $read($db->table('categories'));
        $attributes = $read($db->table('attributes')->whereRaw('BINARY name = BINARY ?', ['Цвет']));
        $ids = array_column($attributes, 'id');
        $snapshot = [
            'database' => 'webv3_attributes_test',
            'categories' => $categories,
            'attributes' => $attributes,
            'links' => $read($db->table('attribute_category')->whereIn('attribute_id', $ids)),
            'colors' => $read($db->table('colors')),
            'palettes' => $read($db->table('attribute_color')->whereIn('attribute_id', $ids)),
            'values' => $read($db->table('attribute_values')->whereIn('attribute_id', $ids)),
        ];

        return (new ColorConversionPlan)->build($snapshot);
    }

    public function apply(string $fingerprint): array
    {
        $db = $this->connection();
        if ($db->transactionLevel() !== 0) {
            throw new ImportException('Требуется отдельная транзакция преобразования.');
        }

        return $db->transaction(function () use ($db, $fingerprint): array {
            $this->connection();
            $plan = $this->snapshot($db, true);
            if (! hash_equals($plan['fingerprint'], $fingerprint)) {
                throw new ImportException('Данные изменились. Повторите предварительный просмотр.');
            }
            if ($plan['attribute_ids'] === [] && $plan['value_changes'] === []) {
                return ['changed' => false, 'journal' => null];
            }
            // Durable before-image, outside the public disk; never overwrite a previous journal.
            // This is a prepared journal, NOT evidence that the SQL transaction committed.
            $directory = storage_path('app/private/attribute-color-conversion');
            if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
                throw new ImportException('Не удалось создать каталог журнала.');
            }
            $path = $directory.'/'.bin2hex(random_bytes(16)).'.json';
            $json = json_encode(['state' => 'prepared', 'plan' => $plan], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            $handle = fopen($path, 'x+b');
            if ($handle === false) {
                throw new ImportException('Не удалось открыть журнал.');
            }
            try {
                if (fwrite($handle, $json) !== strlen($json) || ! fflush($handle) || ! fsync($handle)) {
                    throw new ImportException('Не удалось надёжно сохранить журнал.');
                }
            } finally {
                fclose($handle);
            }
            if (hash_file('sha256', $path) !== hash('sha256', $json)) {
                throw new ImportException('Проверка журнала не пройдена.');
            }
            foreach ($plan['attribute_ids'] as $id) {
                $db->table('attributes')->where('id', $id)->update(['type' => 'color', 'unit' => null, 'options' => null, 'is_filterable' => 1]);
                $db->table('attribute_color')->where('attribute_id', $id)->delete();
                foreach ($plan['color_ids'] as $colorId) {
                    $db->table('attribute_color')->insert(['attribute_id' => $id, 'color_id' => $colorId]);
                }
            }
            foreach ($plan['value_changes'] as $value) {
                // Preserve row ID, product, attribute, timestamps and archive metadata.
                $db->table('attribute_values')->where('id', $value['id'])->update(['value' => $value['after']]);
            }

            return ['changed' => true, 'journal' => $path, 'journal_sha256' => hash('sha256', $json),
                'attributes' => count($plan['attribute_ids']), 'values' => count($plan['value_changes'])];
        });
    }
}
