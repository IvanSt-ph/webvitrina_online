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
            'title'      => 'nullable|string|max:255',
            'image'      => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
            'link'       => 'nullable|url|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'active'     => 'nullable|boolean',
        ]);

        $data['image'] = $request->file('image')->store('banners', 'public');
        $data['active'] = $request->boolean('active');

        Banner::create($data);
        cache()->forget('slides_home');

        return redirect()->route('admin.banners.index')->with('success', '✅ Баннер успешно добавлен.');
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.form', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $request->validate([
            'title'      => 'nullable|string|max:255',
            'image'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'link'       => 'nullable|url|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'active'     => 'nullable|boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($banner->image && Storage::disk('public')->exists($banner->image)) {
                Storage::disk('public')->delete($banner->image);
            }
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $data['active'] = $request->boolean('active');

        $banner->update($data);
        cache()->forget('slides_home');

        return redirect()->route('admin.banners.index')->with('success', '✅ Баннер обновлён.');
    }

    public function destroy(Banner $banner)
    {
        if ($banner->image && Storage::disk('public')->exists($banner->image)) {
            Storage::disk('public')->delete($banner->image);
        }

        $banner->delete();
        cache()->forget('slides_home');

        return back()->with('success', '🗑 Баннер удалён.');
    }


    
}
