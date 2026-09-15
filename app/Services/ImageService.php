<?php

namespace App\Services;

use App\Rules\ImageUploadConstraints;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImageService
{
    // Защищенные изображения (точное совпадение имени файла)
    const PROTECTED_IMAGES = [
        'no-image.png',
        'default-product.png',
        'placeholder.png',
        'default/no-image.png',
        'no-avatar.png',
        'default-avatar.png'
    ];

    /**
     * 📤 Загрузка одного изображения
     */
    public function upload(UploadedFile $file, string $dir): string
    {
        ImageUploadConstraints::assertSafe($file);

        try {
            return $this->uploadOptimized($file, $dir);
        } catch (\Throwable $e) {
            Log::warning('ImageService: optimized upload failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 📤 Загрузка изображения в WebP + создание легкой версии для карточек.
     *
     * Возвращаем medium-путь, который хранится в БД. Thumb лежит рядом:
     * products/2026/05/medium/name.webp
     * products/2026/05/thumb/name.webp
     */
    protected function uploadOptimized(UploadedFile $file, string $dir): string
    {
        $image = $this->decode($file);

        $baseName = (string) Str::uuid() . '.webp';
        $mediumPath = trim($dir, '/') . '/medium/' . $baseName;
        $thumbPath = trim($dir, '/') . '/thumb/' . $baseName;

        $medium = $image->scaleDown(width: 1200, height: 1200);
        $mediumContents = $medium->toWebp(82)->toString();
        $thumbContents = (clone $medium)
            ->scaleDown(width: 480, height: 480)
            ->toWebp(78)
            ->toString();

        try {
            $disk = Storage::disk('public');

            if (! $disk->put($mediumPath, $mediumContents) || ! $disk->put($thumbPath, $thumbContents)) {
                throw new \RuntimeException('Не удалось сохранить обработанное изображение.');
            }
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete([$mediumPath, $thumbPath]);

            throw $exception;
        }

        return $mediumPath;
    }

    protected function decode(UploadedFile $file): ImageInterface
    {
        return (new ImageManager(new Driver()))->read($file->getRealPath());
    }

    public static function thumbPath(string $path): string
    {
        $clean = ltrim(str_replace(['storage/', '/storage/'], '', $path), '/');
        $dir = dirname($clean);
        $base = basename($clean);

        if (basename($dir) === 'medium') {
            return dirname($dir) . '/thumb/' . $base;
        }

        return $dir . '/thumb/' . pathinfo($base, PATHINFO_FILENAME) . '.webp';
    }

    /**
     * 📤 Загрузка массива изображений (галерея)
     */
    public function uploadGallery(array $files, string $dir): array
    {
        $paths = [];

        try {
            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $paths[] = $this->upload($file, $dir);
                }
            }
        } catch (\Throwable $exception) {
            $this->deleteMany($paths);

            throw $exception;
        }

        return $paths;
    }

    /**
     * 🧹 Удаление одной картинки (С ЗАЩИТОЙ - только точное совпадение)
     */
    public function delete(?string $path): void
    {
        if (empty($path)) {
            return;
        }

        // 🔥 Защита: только точное совпадение имени файла
        if ($this->isProtectedImage($path)) {
            Log::warning("🛡️ ImageService: попытка удалить защищенное изображение: {$path}");
            return;
        }

        // Убираем префикс storage/
        $clean = ltrim(str_replace(['storage/', '/storage/'], '', $path), '/');

        if ($clean === '' || str_contains($clean, '[')) {
            return;
        }

        if (Storage::disk('public')->exists($clean)) {
            Storage::disk('public')->delete($clean);
            Log::info("✅ ImageService: удалено: {$clean}");
        }

        $thumb = self::thumbPath($clean);
        if ($thumb !== $clean && Storage::disk('public')->exists($thumb)) {
            Storage::disk('public')->delete($thumb);
            Log::info("✅ ImageService: удалена миниатюра: {$thumb}");
        }
    }

    /**
     * Проверяет, является ли изображение защищенным
     * ТОЛЬКО точное совпадение имени файла - безопасно!
     */
    protected function isProtectedImage(string $path): bool
    {
        $basename = basename($path);
        return in_array($basename, self::PROTECTED_IMAGES, true);
    }

    /**
     * 🧹 Удаление множества картинок
     */
    public function deleteMany(array $paths): void
    {
        foreach ($paths as $path) {
            $this->delete($path);
        }
    }

    /**
     * 🔍 Проверка существования фото
     */
    public function exists(string $path): bool
    {
        $clean = ltrim(str_replace(['storage/', '/storage/'], '', $path), '/');
        return Storage::disk('public')->exists($clean);
    }

    /**
     * 📁 Генерация рабочего пути
     */
    public function makeDir(string $base = 'products'): string
    {
        return $base . '/' . date('Y/m');
    }
}
