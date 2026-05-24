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
        request()->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:active,hidden,missing_mobile'],
            'sort' => ['nullable', 'in:latest,oldest,title,order_asc,order_desc'],
        ]);

        $summary = [
            'total' => Banner::count(),
            'active' => Banner::where('active', true)->count(),
            'hidden' => Banner::where('active', false)->count(),
            'mobile_ready' => Banner::whereNotNull('image_mobile')->count(),
        ];

        $query = Banner::query();

        if (request()->filled('q')) {
            $search = trim((string) request('q'));
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('link', 'like', "%{$search}%");

                if (ctype_digit($search)) {
                    $subQuery->orWhere('id', (int) $search);
                }
            });
        }

        match (request('status')) {
            'active' => $query->where('active', true),
            'hidden' => $query->where('active', false),
            'missing_mobile' => $query->whereNull('image_mobile'),
            default => null,
        };

        match (request('sort', 'order_asc')) {
            'latest' => $query->latest(),
            'oldest' => $query->oldest(),
            'title' => $query->orderBy('title'),
            'order_desc' => $query->orderByDesc('sort_order')->latest(),
            default => $query->orderBy('sort_order')->latest(),
        };

        $banners = $query->paginate(12)->withQueryString();

        return view('admin.banners.index', compact('banners', 'summary'));
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
            'recrop_existing' => 'nullable|boolean',
            'mobile_recrop_existing' => 'nullable|boolean',
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
            $data['recrop_existing'],
            $data['mobile_recrop_existing'],
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
            'recrop_existing' => 'nullable|boolean',
            'mobile_recrop_existing' => 'nullable|boolean',
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
            $this->deleteBannerImage($banner->image);
            $data['image'] = null;
        } elseif ($request->boolean('recrop_existing')) {
            $sourcePath = $this->bannerSourcePath($banner);

            if ($sourcePath) {
                $crop = $this->cropData($request);
                $newImages = [];

                foreach (['desktop', 'tablet', 'mobile'] as $device) {
                    $newImages["image_{$device}"] = $this->uploadBannerImageFromDisk($sourcePath, $device, $crop);
                }

                foreach (['image', 'image_desktop', 'image_tablet', 'image_mobile'] as $key) {
                    $this->deleteBannerImage($banner->$key);
                }

                $data = array_merge($data, $newImages);
                $data['image'] = null;
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

        if (! $request->hasFile('image_source') && ! $request->hasFile('image_mobile') && $request->boolean('mobile_recrop_existing')) {
            $sourcePath = $this->mobileBannerSourcePath($banner);

            if ($sourcePath) {
                $newMobileImage = $this->uploadBannerImageFromDisk(
                    $sourcePath,
                    'mobile',
                    $this->cropData($request, 'mobile_crop_'),
                );

                $this->deleteBannerImage($banner->image_mobile);
                $data['image_mobile'] = $newMobileImage;
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
            $data['recrop_existing'],
            $data['mobile_recrop_existing'],
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
        $manager = new ImageManager(new Driver());

        return $this->storeProcessedBannerImage(
            $manager->read($file->getRealPath()),
            $device,
            $crop,
        );
    }

    private function uploadBannerImageFromDisk(string $sourcePath, string $device, ?array $crop = null): string
    {
        $manager = new ImageManager(new Driver());

        return $this->storeProcessedBannerImage(
            $manager->read(Storage::disk('public')->path($sourcePath)),
            $device,
            $crop,
        );
    }

    private function storeProcessedBannerImage($image, string $device, ?array $crop = null): string
    {
        $sizes = [
            'desktop' => [2400, 720],
            'tablet' => [1600, 600],
            'mobile' => [960, 480],
        ];

        [$width, $height] = $sizes[$device] ?? $sizes['desktop'];

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

    private function bannerSourcePath(Banner $banner): ?string
    {
        return collect([
            $banner->image_desktop,
            $banner->image_tablet,
            $banner->image_mobile,
            $banner->image,
        ])->first(fn (?string $path): bool => $this->bannerImageExists($path));
    }

    private function mobileBannerSourcePath(Banner $banner): ?string
    {
        return collect([
            $banner->image_mobile,
            $banner->image_desktop,
            $banner->image_tablet,
            $banner->image,
        ])->first(fn (?string $path): bool => $this->bannerImageExists($path));
    }

    private function bannerImageExists(?string $path): bool
    {
        return $path !== null && Storage::disk('public')->exists($path);
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
