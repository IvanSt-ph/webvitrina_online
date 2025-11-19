<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageService
{
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
     * 🧹 Удаление одной картинки
     */
    public function delete(?string $path): void
    {
        if (!$path) return;

        // Убираем префикс storage/
        $clean = ltrim(str_replace(['storage/', '/storage/'], '', $path), '/');

        if ($clean === '' || str_contains($clean, '[')) {
            return; // защита от некорректных JSON-путей
        }

        // Удаляем из стораджа
        if (Storage::disk('public')->exists($clean)) {
            Storage::disk('public')->delete($clean);
        }
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
     * 📁 Генерация рабочего пути (если ещё понадобится)
     * Например: products/2025/01/file.jpg
     */
    public function makeDir(string $base = 'products'): string
    {
        return $base . '/' . date('Y/m');
    }
}
