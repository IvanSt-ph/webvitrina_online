<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'link'           => 'nullable|string|max:255',
            'sort_order'     => 'nullable|integer|min:0',
            'active'         => 'nullable|boolean',
            'image_desktop'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'image_tablet'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'image_mobile'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        // 🔹 Сохраняем файлы, если загружены
        foreach (['desktop', 'tablet', 'mobile'] as $device) {
            $key = "image_{$device}";
            if ($request->hasFile($key)) {
                $data[$key] = $request->file($key)->store('banners', 'public');
            }
        }

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
            'link'           => 'nullable|string|max:255',
            'sort_order'     => 'nullable|integer|min:0',
            'active'         => 'nullable|boolean',
            'image_desktop'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'image_tablet'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'image_mobile'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        // 🔹 Если новые изображения загружены — обновляем их и удаляем старые
        foreach (['desktop', 'tablet', 'mobile'] as $device) {
            $key = "image_{$device}";
            if ($request->hasFile($key)) {
                if ($banner->$key && Storage::disk('public')->exists($banner->$key)) {
                    Storage::disk('public')->delete($banner->$key);
                }
                $data[$key] = $request->file($key)->store('banners', 'public');
            }
        }

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
            if ($banner->$col && Storage::disk('public')->exists($banner->$col)) {
                Storage::disk('public')->delete($banner->$col);
            }
        }

        $banner->delete();
        cache()->forget('slides_home');

        return back()->with('success', '🗑 Баннер удалён.');
    }
}
