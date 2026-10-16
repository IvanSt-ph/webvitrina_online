<?php

namespace App\Services;

use App\Models\ReviewImage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReviewImageStorage
{
    public const DISK = 'review_images';

    public const LEGACY_DISK = 'public';

    public function path(ReviewImage|string $image, string $variant): ?string
    {
        $path = $image instanceof ReviewImage ? $image->path : $image;
        $path = $this->canonicalPath($path);

        if ($path === null || !in_array($variant, ['medium', 'thumb'], true)) {
            return null;
        }

        return $variant === 'thumb' ? ImageService::thumbPath($path) : $path;
    }

    public function canonicalPath(?string $path): ?string
    {
        $path = \App\Support\PublicImage::path($path);

        return $path !== null && str_starts_with($path, 'reviews/') ? $path : null;
    }

    /** @return array{0: FilesystemAdapter, 1: string}|null */
    public function readable(ReviewImage|string $image, string $variant): ?array
    {
        $path = $this->path($image, $variant);
        if ($path === null) {
            return null;
        }

        foreach ([self::DISK, self::LEGACY_DISK] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($path)) {
                return [$disk, $path];
            }
        }

        return null;
    }

    public function response(ReviewImage $image, string $variant): ?BinaryFileResponse
    {
        $readable = $this->readable($image, $variant);
        if ($readable === null) {
            return null;
        }

        [$disk, $path] = $readable;
        $file = realpath($disk->path($path));
        $root = realpath($disk->path(''));
        if ($file === false || $root === false || !is_file($file)
            || !$this->isInsideRoot($file, $root)) {
            return null;
        }
        $mime = mime_content_type($file);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        $response = response()->file($file, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
        $response->setPrivate();
        $response->setMaxAge(0);

        return $response;
    }

    private function isInsideRoot(string $path, string $root): bool
    {
        $normalize = static function (string $value): string {
            $value = rtrim(str_replace('\\', '/', $value), '/');

            return PHP_OS_FAMILY === 'Windows' ? strtolower($value) : $value;
        };
        $path = $normalize($path);
        $root = $normalize($root);

        return $path !== $root && str_starts_with($path, $root.'/');
    }

    public function deleteEverywhere(?string $path, bool $throwOnFailure = false): void
    {
        $path = $this->canonicalPath($path);
        if ($path === null) {
            return;
        }

        $failed = [];
        foreach ([self::DISK, self::LEGACY_DISK] as $diskName) {
            foreach (array_unique([$path, ImageService::thumbPath($path)]) as $file) {
                try {
                    $disk = Storage::disk($diskName);
                    if ($disk->exists($file) && !$disk->delete($file)) {
                        $failed[] = $diskName.':'.$file;
                    }
                } catch (\Throwable) {
                    $failed[] = $diskName.':'.$file;
                }
            }
        }

        if ($failed !== [] && $throwOnFailure) {
            throw new \RuntimeException('Review image cleanup failed: '.implode(', ', $failed));
        }
    }

    public function deleteIfUnreferenced(?string $path, bool $throwOnFailure = false): void
    {
        $path = $this->canonicalPath($path);
        if ($path === null) {
            return;
        }

        if (ReviewImage::query()->whereIn('path', [
            $path,
            'storage/'.$path,
            '/storage/'.$path,
        ])->exists()) {
            return;
        }

        $this->deleteEverywhere($path, $throwOnFailure);
    }
}
