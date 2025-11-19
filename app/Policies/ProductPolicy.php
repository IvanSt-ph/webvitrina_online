<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Глобальный override — админ может всё
     */
    public function before(User $user, string $ability)
    {
        if ($user->role === 'admin') {
            return true;
        }
    }

    /**
     * Может ли пользователь обновить товар
     */
    public function update(User $user, Product $product): bool
    {
        return $product->user_id === $user->id;
    }

    /**
     * Может ли пользователь удалить товар
     */
    public function delete(User $user, Product $product): bool
    {
        return $product->user_id === $user->id;
    }
}
