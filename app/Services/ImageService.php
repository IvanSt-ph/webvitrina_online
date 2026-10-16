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
    public function upload(UploadedFile $file, string $dir, string $diskName = 'public'): string
    {
        ImageUploadConstraints::assertSafe($file);

        try {
            return $this->uploadOptimized($file, $dir, $diskName);
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
    protected function uploadOptimized(UploadedFile $file, string $dir, string $diskName = 'public'): string
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
            $disk = Storage::disk($diskName);

            if (! $disk->put($mediumPath, $mediumContents) || ! $disk->put($thumbPath, $thumbContents)) {
                throw new \RuntimeException('Не удалось сохранить обработанное изображение.');
            }
        } catch (\Throwable $exception) {
            try {
                $this->delete($mediumPath, throwOnFailure: true, diskName: $diskName);
            } catch (\Throwable $cleanupException) {
                Log::error('Image upload cleanup failed; retry required', [
                    'path' => $mediumPath, 'error' => $cleanupException->getMessage(),
                ]);
            }

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
    public function uploadGallery(array $files, string $dir, string $diskName = 'public'): array
    {
        $paths = [];

        try {
            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $paths[] = $this->upload($file, $dir, $diskName);
                }
            }
        } catch (\Throwable $exception) {
            $this->deleteMany($paths, $diskName);

            throw $exception;
        }

        return $paths;
    }

    /**
     * 🧹 Удаление одной картинки (С ЗАЩИТОЙ - только точное совпадение)
     */
    public function delete(?string $path, bool $throwOnFailure = false, string $diskName = 'public'): void
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

        $failed = [];
        foreach (array_unique([$clean, self::thumbPath($clean)]) as $file) {
            try {
                if (Storage::disk($diskName)->exists($file) && ! Storage::disk($diskName)->delete($file)) {
                    $failed[] = $file;
                }
            } catch (\Throwable $exception) {
                $failed[] = $file;
            }
        }
        if ($failed) {
            if ($throwOnFailure) {
                throw new \RuntimeException('Image cleanup failed: ' . implode(', ', $failed));
            }
            Log::error('Image cleanup failed; retry required', ['paths' => $failed]);
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
    public function deleteMany(array $paths, string $diskName = 'public'): void
    {
        foreach ($paths as $path) {
            $this->delete($path, diskName: $diskName);
        }
    }

    /**
     * 🔍 Проверка существования фото
     */
    public function exists(string $path, string $diskName = 'public'): bool
    {
        $clean = ltrim(str_replace(['storage/', '/storage/'], '', $path), '/');
        return Storage::disk($diskName)->exists($clean);
    }

    /**
     * 📁 Генерация рабочего пути
     */
    public function makeDir(string $base = 'products'): string
    {
        return $base . '/' . date('Y/m');
    }
}
