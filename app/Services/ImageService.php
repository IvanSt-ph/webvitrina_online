<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
        return $file->store($dir, 'public');
    }

    /**
     * 📤 Загрузка массива изображений (галерея)
     */
    public function uploadGallery(array $files, string $dir): array
    {
        $paths = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $paths[] = $this->upload($file, $dir);
            }
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