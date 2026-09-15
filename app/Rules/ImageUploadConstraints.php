<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

final class ImageUploadConstraints implements ValidationRule
{
    public const MAX_WIDTH = 8000;

    public const MAX_HEIGHT = 8000;

    public const MAX_PIXELS = 16_000_000;

    public const MAX_FILE_KILOBYTES = 8192;

    public const MAX_GALLERY_IMAGES = 10;

    private const ALLOWED_IMAGE_TYPES = [
        IMAGETYPE_JPEG,
        IMAGETYPE_PNG,
        IMAGETYPE_WEBP,
    ];

    public static function rules(int $maxKilobytes, bool $required = false): array
    {
        return [
            'bail',
            $required ? 'required' : 'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:' . $maxKilobytes,
            new self(),
        ];
    }

    public static function assertSafe(UploadedFile $file, int $maxKilobytes = self::MAX_FILE_KILOBYTES): void
    {
        if (! $file->isValid()) {
            throw new InvalidArgumentException('Не удалось загрузить изображение.');
        }

        $size = $file->getSize();

        if ($size === false || $size > $maxKilobytes * 1024) {
            throw new InvalidArgumentException("Размер изображения не должен превышать {$maxKilobytes} КБ.");
        }

        $path = $file->getRealPath();

        if (! is_string($path)) {
            throw new InvalidArgumentException('Не удалось прочитать загруженное изображение.');
        }

        self::assertSafePath($path, $maxKilobytes);
    }

    public static function assertSafePath(string $path, int $maxKilobytes = self::MAX_FILE_KILOBYTES): void
    {
        $size = @filesize($path);

        if ($size === false || $size > $maxKilobytes * 1024) {
            throw new InvalidArgumentException("Размер изображения не должен превышать {$maxKilobytes} КБ.");
        }

        $metadata = @getimagesize($path);

        if ($metadata === false || ! isset($metadata[0], $metadata[1], $metadata[2])) {
            throw new InvalidArgumentException('Файл должен быть корректным изображением JPG, PNG или WebP.');
        }

        [$width, $height, $type] = $metadata;

        if (! in_array($type, self::ALLOWED_IMAGE_TYPES, true)) {
            throw new InvalidArgumentException('Поддерживаются только изображения JPG, PNG и WebP.');
        }

        if ($width < 1 || $height < 1) {
            throw new InvalidArgumentException('Изображение имеет некорректные размеры.');
        }

        if ($width > self::MAX_WIDTH) {
            throw new InvalidArgumentException('Ширина изображения не должна превышать ' . self::MAX_WIDTH . ' пикселей.');
        }

        if ($height > self::MAX_HEIGHT) {
            throw new InvalidArgumentException('Высота изображения не должна превышать ' . self::MAX_HEIGHT . ' пикселей.');
        }

        if ($height > intdiv(self::MAX_PIXELS, $width)) {
            throw new InvalidArgumentException('Разрешение изображения не должно превышать 16 мегапикселей.');
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('Поле :attribute должно быть загруженным изображением.');

            return;
        }

        try {
            self::assertSafe($value);
        } catch (InvalidArgumentException $exception) {
            $fail($exception->getMessage());
        }
    }
}
