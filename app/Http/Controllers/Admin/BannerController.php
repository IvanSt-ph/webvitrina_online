<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('sort_order')->get();
        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.form', ['banner' => new Banner()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'          => 'nullable|string|max:255',
            'link'           => $this->linkRules(),
            'sort_order'     => 'nullable|integer|min:0',
            'active'         => 'nullable|boolean',
            'image_source'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'crop_x'         => 'nullable|numeric|min:0|max:100',
            'crop_y'         => 'nullable|numeric|min:0|max:100',
            'crop_w'         => 'nullable|numeric|min:10|max:100',
            'crop_h'         => 'nullable|numeric|min:10|max:100',
            'mobile_crop_x'  => 'nullable|numeric|min:0|max:100',
            'mobile_crop_y'  => 'nullable|numeric|min:0|max:100',
            'mobile_crop_w'  => 'nullable|numeric|min:10|max:100',
            'mobile_crop_h'  => 'nullable|numeric|min:10|max:100',
            'image_desktop'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'image_tablet'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'image_mobile'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        if ($request->hasFile('image_source')) {
            $crop = $this->cropData($request);
            foreach (['desktop', 'tablet', 'mobile'] as $device) {
                $data["image_{$device}"] = $this->uploadBannerImage(
                    $request->file('image_source'),
                    $device,
                    $crop,
                );
            }
        }

        foreach (['desktop', 'tablet', 'mobile'] as $device) {
            $key = "image_{$device}";
            if ($request->hasFile($key)) {
                $data[$key] = $this->uploadBannerImage(
                    $request->file($key),
                    $device,
                    $device === 'mobile' ? $this->cropData($request, 'mobile_crop_') : null,
                );
            }
        }

        unset(
            $data['image_source'],
            $data['crop_x'],
            $data['crop_y'],
            $data['crop_w'],
            $data['crop_h'],
            $data['mobile_crop_x'],
            $data['mobile_crop_y'],
            $data['mobile_crop_w'],
            $data['mobile_crop_h'],
        );

        // 🔹 Флаг активности
        $data['active'] = $request->boolean('active');

        // 🔹 Старое поле image больше не нужно, но пусть будет null
        $data['image'] = null;

        Banner::create($data);
        cache()->forget('slides_home');

        return redirect()
            ->route('admin.banners.index')
            ->with('success', '✅ Баннер успешно добавлен.');
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.form', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $request->validate([
            'title'          => 'nullable|string|max:255',
            'link'           => $this->linkRules(),
            'sort_order'     => 'nullable|integer|min:0',
            'active'         => 'nullable|boolean',
            'image_source'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'crop_x'         => 'nullable|numeric|min:0|max:100',
            'crop_y'         => 'nullable|numeric|min:0|max:100',
            'crop_w'         => 'nullable|numeric|min:10|max:100',
            'crop_h'         => 'nullable|numeric|min:10|max:100',
            'mobile_crop_x'  => 'nullable|numeric|min:0|max:100',
            'mobile_crop_y'  => 'nullable|numeric|min:0|max:100',
            'mobile_crop_w'  => 'nullable|numeric|min:10|max:100',
            'mobile_crop_h'  => 'nullable|numeric|min:10|max:100',
            'image_desktop'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'image_tablet'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'image_mobile'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        if ($request->hasFile('image_source')) {
            $crop = $this->cropData($request);
            foreach (['desktop', 'tablet', 'mobile'] as $device) {
                $key = "image_{$device}";
                $this->deleteBannerImage($banner->$key);
                $data[$key] = $this->uploadBannerImage(
                    $request->file('image_source'),
                    $device,
                    $crop,
                );
            }
        }

        foreach (['desktop', 'tablet', 'mobile'] as $device) {
            $key = "image_{$device}";
            if ($request->hasFile($key)) {
                $this->deleteBannerImage($banner->$key);
                $data[$key] = $this->uploadBannerImage(
                    $request->file($key),
                    $device,
                    $device === 'mobile' ? $this->cropData($request, 'mobile_crop_') : null,
                );
            }
        }

        unset(
            $data['image_source'],
            $data['crop_x'],
            $data['crop_y'],
            $data['crop_w'],
            $data['crop_h'],
            $data['mobile_crop_x'],
            $data['mobile_crop_y'],
            $data['mobile_crop_w'],
            $data['mobile_crop_h'],
        );

        // 🔹 Активность
        $data['active'] = $request->boolean('active');

        // 🔹 Сохраняем изменения
        $banner->update($data);
        cache()->forget('slides_home');

        return redirect()
            ->route('admin.banners.index')
            ->with('success', '✅ Баннер обновлён.');
    }

    public function destroy(Banner $banner)
    {
        // 🔹 Удаляем все изображения
        foreach (['image', 'image_desktop', 'image_tablet', 'image_mobile'] as $col) {
            $this->deleteBannerImage($banner->$col);
        }

        $banner->delete();
        cache()->forget('slides_home');

        return back()->with('success', '🗑 Баннер удалён.');
    }

    private function linkRules(): array
    {
        return [
            'nullable',
            'string',
            'max:255',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || $value === '') {
                    return;
                }

                if (str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
                    return;
                }

                $scheme = parse_url($value, PHP_URL_SCHEME);

                if (in_array($scheme, ['http', 'https'], true) && filter_var($value, FILTER_VALIDATE_URL)) {
                    return;
                }

                $fail('Ссылка баннера должна быть внутренним путём /... или URL с http/https.');
            },
        ];
    }

    private function uploadBannerImage(
        \Illuminate\Http\UploadedFile $file,
        string $device,
        ?array $crop = null
    ): string
    {
        $sizes = [
            'desktop' => [1920, 720],
            'tablet' => [1400, 600],
            'mobile' => [960, 480],
        ];

        [$width, $height] = $sizes[$device] ?? $sizes['desktop'];

        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());

        if ($crop) {
            $sourceWidth = $image->width();
            $sourceHeight = $image->height();
            $cropWidth = max(1, min($sourceWidth, (int) round($sourceWidth * $crop['w'] / 100)));
            $cropHeight = max(1, min($sourceHeight, (int) round($sourceHeight * $crop['h'] / 100)));
            $cropX = max(0, min($sourceWidth - $cropWidth, (int) round($sourceWidth * $crop['x'] / 100)));
            $cropY = max(0, min($sourceHeight - $cropHeight, (int) round($sourceHeight * $crop['y'] / 100)));

            $image = $image->crop($cropWidth, $cropHeight, $cropX, $cropY);
        }

        $image = $image
            ->coverDown(width: $width, height: $height)
            ->toWebp(82);

        $path = 'banners/' . $device . '/' . Str::uuid() . '.webp';

        Storage::disk('public')->put($path, $image->toString());

        return $path;
    }

    private function cropData(Request $request, string $prefix = 'crop_'): array
    {
        $x = (float) $request->input($prefix . 'x', 0);
        $y = (float) $request->input($prefix . 'y', 0);
        $w = (float) $request->input($prefix . 'w', 100);
        $h = (float) $request->input($prefix . 'h', 100);

        return [
            'x' => max(0, min(100 - $w, $x)),
            'y' => max(0, min(100 - $h, $y)),
            'w' => max(10, min(100, $w)),
            'h' => max(10, min(100, $h)),
        ];
    }

    private function deleteBannerImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
