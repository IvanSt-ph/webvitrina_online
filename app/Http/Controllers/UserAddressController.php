<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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

        $this->withUserLock(function () use ($data): void {
            if (!empty($data['is_default'])) {
                $this->resetDefaultForUser();
            }
            $this->saveAddress(new UserAddress($data));
        });

        return back()->with('success', '✅ Адрес добавлен');
    }


    /**
     * Обновление адреса
     */
    public function update(Request $request, UserAddress $address)
    {
        $this->authorizeOwner($address);

        $data = $this->validateData($request);

        $this->withUserLock(function () use ($address, $data): void {
            $current = $this->lockedAddress($address);
            if (!empty($data['is_default'])) {
                $this->resetDefaultForUser($current->id);
            }
            $this->saveAddress($current->fill($data));
        });

        return back()->with('success', '✅ Адрес обновлён');
    }


    /**
     * Удаление адреса
     */
    public function destroy(UserAddress $address)
    {
        $this->authorizeOwner($address);

        $this->withUserLock(function () use ($address): void {
            $current = $this->lockedAddress($address);
            if (! $current->delete()) {
                throw new RuntimeException('Address deletion was not completed.');
            }
        });

        return back()->with('success', '🗑️ Адрес удалён');
    }


    /**
     * Сделать адрес основным
     */
    public function makeDefault(UserAddress $address)
    {
        $this->authorizeOwner($address);

        $this->withUserLock(function () use ($address): void {
            $current = $this->lockedAddress($address);
            $this->resetDefaultForUser($current->id);
            $this->saveAddress($current->fill(['is_default' => true]));
        });

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

    private function withUserLock(callable $operation): mixed
    {
        return DB::transaction(function () use ($operation) {
            // One stable lock even for an empty address book. Keep the same
            // user -> address order as account deletion; never lock only defaults.
            User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();

            return $operation();
        });
    }

    private function lockedAddress(UserAddress $bound): UserAddress
    {
        // Route binding may predate a competing update/delete. Validate the
        // current row before resetting any other address's default flag.
        $current = UserAddress::whereKey($bound->getKey())->lockForUpdate()->firstOrFail();
        $this->authorizeOwner($current);

        return $current;
    }

    private function saveAddress(UserAddress $address): void
    {
        // Model event listeners can cancel save without throwing an exception.
        if (! $address->save()) {
            throw new RuntimeException('Address save was not completed.');
        }
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
     * Сбрасывает default-флаг остальных адресов под блокировкой пользователя.
     */
    private function resetDefaultForUser(?int $exceptId = null): void
    {
        UserAddress::where('user_id', auth()->id())
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->update(['is_default' => false]);
    }
}
