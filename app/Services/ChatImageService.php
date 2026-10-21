<?php

namespace App\Services;

use App\Rules\ImageUploadConstraints;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ChatImageService
{
    public function upload(UploadedFile $file): string
    {
        return $this->storePrepared($this->prepare($file));
    }

    public function prepare(UploadedFile $file): string
    {
        ImageUploadConstraints::assertSafe($file, 5120);

        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());

        return $image->scaleDown(width: 1600, height: 1600)->toWebp(82)->toString();
    }

    public function storePrepared(string $bytes): string
    {
        $path = 'chat-images/' . date('Y/m') . '/' . Str::uuid() . '.webp';
        try {
            if (! Storage::disk('local')->put($path, $bytes)) {
                throw new \RuntimeException('Не удалось сохранить обработанное изображение чата.');
            }
        } catch (\Throwable $exception) {
            $this->cleanup($path, 'failed_upload');
            throw $exception;
        }

        return $path;
    }

    public function cleanup(string $path, string $phase): void
    {
        try {
            if (! Storage::disk('local')->delete($path)) {
                throw new \RuntimeException('Cannot delete private chat image.');
            }
        } catch (\Throwable $exception) {
            Log::error('Chat image cleanup failed; retry required', [
                'path' => $path, 'phase' => $phase, 'error' => $exception->getMessage(),
            ]);
        }
    }
}
