<?php

namespace App\Policies;

use App\Models\CartItem;
use App\Models\User;

class CartItemPolicy
{
    /**
     * Любое действие с CartItem разрешено только владельцу.
     */
    public function view(User $user, CartItem $item): bool
    {
        return $item->user_id === $user->id;
    }

    public function update(User $user, CartItem $item): bool
    {
        return $item->user_id === $user->id;
    }

    public function delete(User $user, CartItem $item): bool
    {
        return $item->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        // создавать строки корзины можно любому авторизованному (доп. проверка в контроллере)
        return $user !== null;
    }
}
