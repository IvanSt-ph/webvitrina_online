<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;

class UserAddressController extends Controller
{
    /**
     * Список адресов
     */
    public function index()
    {
        $addresses = UserAddress::where('user_id', auth()->id())
            ->orderByDesc('is_default')
            ->get();

        return view('profile.addresses', compact('addresses'));
    }


    /**
     * Создание нового адреса
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $data['user_id'] = auth()->id();

        // Установка как дефолтный -> сбрасываем остальные
        if (!empty($data['is_default'])) {
            $this->resetDefaultForUser();
        }

        UserAddress::create($data);

        return back()->with('success', '✅ Адрес добавлен');
    }


    /**
     * Обновление адреса
     */
    public function update(Request $request, UserAddress $address)
    {
        $this->authorizeOwner($address);

        $data = $this->validateData($request);

        // Если ставим дефолтным -> сбрасываем другие
        if (!empty($data['is_default'])) {
            $this->resetDefaultForUser();
        }

        $address->update($data);

        return back()->with('success', '✅ Адрес обновлён');
    }


    /**
     * Удаление адреса
     */
    public function destroy(UserAddress $address)
    {
        $this->authorizeOwner($address);

        $address->delete();

        return back()->with('success', '🗑️ Адрес удалён');
    }


    /**
     * Сделать адрес основным
     */
    public function makeDefault(UserAddress $address)
    {
        $this->authorizeOwner($address);

        $this->resetDefaultForUser();

        $address->update(['is_default' => true]);

        return back()->with('success', '🏠 Адрес установлен по умолчанию');
    }


    /* ======================================================
     | 🔧 ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
     ====================================================== */

    /**
     * Проверка что адрес принадлежит текущему юзеру
     */
    private function authorizeOwner(UserAddress $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);
    }

    /**
     * Валидация данных (общая для store и update)
     */
    private function validateData(Request $request)
    {
        return $request->validate([
            'country'      => 'required|string|max:100',
            'city'         => 'required|string|max:100',
            'street'       => 'required|string|max:150',
            'house'        => 'nullable|string|max:50',
            'entrance'     => 'nullable|string|max:50',
            'apartment'    => 'nullable|string|max:50',
            'postal_code'  => 'nullable|string|max:20',
            'comment'      => 'nullable|string|max:500',
            'is_default'   => 'sometimes|boolean',
        ]);
    }

    /**
     * Сбрасывает default-флаг у ВСЕХ адресов пользователя
     */
    private function resetDefaultForUser()
    {
        UserAddress::where('user_id', auth()->id())
            ->update(['is_default' => false]);
    }
}
