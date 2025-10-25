<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;

class UserAddressController extends Controller
{
    public function index()
    {
        $addresses = UserAddress::where('user_id', auth()->id())
            ->orderByDesc('is_default')
            ->get();

        return view('profile.addresses', compact('addresses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
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

        $data['user_id'] = auth()->id();
        $data['is_default'] = (bool)($data['is_default'] ?? false);

        if ($data['is_default']) {
            UserAddress::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        UserAddress::create($data);

        return back()->with('success', '✅ Адрес добавлен');
    }

    public function update(Request $request, UserAddress $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $data = $request->validate([
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

        if (!empty($data['is_default'])) {
            UserAddress::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        $address->update($data);

        return back()->with('success', '✅ Адрес обновлён');
    }

    public function destroy(UserAddress $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $address->delete();

        return back()->with('success', '🗑️ Адрес удалён');
    }

    public function makeDefault(UserAddress $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);

        UserAddress::where('user_id', auth()->id())->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return back()->with('success', '🏠 Адрес установлен по умолчанию');
    }
}
