<?php

namespace App\Services;

use App\Rules\ImageUploadConstraints;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ChatImageService
{
    public function upload(UploadedFile $file): string
    {
        ImageUploadConstraints::assertSafe($file, 5120);

        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());

        $path = 'chat-images/' . date('Y/m') . '/' . Str::uuid() . '.webp';

        if (! Storage::disk('local')->put(
            $path,
            $image->scaleDown(width: 1600, height: 1600)->toWebp(82)->toString()
        )) {
            throw new \RuntimeException('Не удалось сохранить обработанное изображение чата.');
        }

        return $path;
    }
}
