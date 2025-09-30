<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Может ли пользователь обновить товар
     */
    public function update(User $user, Product $product): bool
    {
        // Только владелец товара может редактировать
        return $product->user_id === $user->id;
    }

    /**
     * Может ли пользователь удалить товар
     */
    public function delete(User $user, Product $product): bool
    {
        // Только владелец товара может удалять
        return $product->user_id === $user->id;
    }
}
