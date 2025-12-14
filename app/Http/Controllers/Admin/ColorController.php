<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    /**
     * 📄 Список цветов
     */
    public function index()
    {
        // Загружаем с количеством использований
        $colors = Color::withCount('attributes')
            ->orderBy('name')
            ->get();

        return view('admin.colors.index', compact('colors'));
    }

    /**
     * ➕ Создание цвета
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:colors,name'],
            'hex'  => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/', 'unique:colors,hex'],
        ]);

        Color::create($data);

        return back()->with('success', 'Цвет успешно добавлен');
    }

    /**
     * ✏️ Обновление цвета (на будущее)
     */
    public function update(Request $request, Color $color)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:colors,name,' . $color->id],
            'hex'  => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/', 'unique:colors,hex,' . $color->id],
        ]);

        $color->update($data);

        return back()->with('success', 'Цвет обновлён');
    }

    /**
     * 🗑 Удаление цвета
     */
    public function destroy(Color $color)
    {
        // Защита: если используется — не удаляем
        if ($color->attributes_count > 0) {
            return back()->with('error', 'Нельзя удалить цвет — он используется в атрибутах');
        }


        $color->delete();

        return back()->with('success', 'Цвет удалён');
    }
}
