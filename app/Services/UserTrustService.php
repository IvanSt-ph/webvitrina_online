<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Collection;

class UserTrustService
{
    public function profileFor(User $user): array
    {
        $completedOrders = $user->orders()
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->count();
        $writtenReviews = Review::query()
            ->where('user_id', $user->id)
            ->where('status', Review::STATUS_APPROVED)
            ->count();
        $sellerReviews = $user->isSeller()
            ? $user->reviews()->where('reviews.status', Review::STATUS_APPROVED)->count()
            : 0;

        $score = 30
            + ($user->hasVerifiedEmail() ? 18 : 0)
            + ($user->hasVerifiedPhone() ? 18 : 0)
            + min(14, $completedOrders * 3)
            + min(10, $writtenReviews * 2)
            + ($user->shop ? 5 : 0)
            + min(5, $sellerReviews);

        $score = min(100, $score);

        $tier = match (true) {
            $score >= 90 => [
                'label' => 'Платиновый уровень',
                'short_label' => 'Платина',
                'icon' => '💎',
                'class' => 'border-cyan-200 bg-cyan-50 text-cyan-800',
                'bar' => 'bg-cyan-500',
                'description' => 'Очень сильный профиль с подтверждениями и активностью.',
            ],
            $score >= 75 => [
                'label' => 'Золотой уровень',
                'short_label' => 'Золото',
                'icon' => '🥇',
                'class' => 'border-amber-200 bg-amber-50 text-amber-800',
                'bar' => 'bg-amber-500',
                'description' => 'Высокий уровень доверия и хорошая активность.',
            ],
            $score >= 60 => [
                'label' => 'Серебряный уровень',
                'short_label' => 'Серебро',
                'icon' => '🥈',
                'class' => 'border-slate-200 bg-slate-50 text-slate-700',
                'bar' => 'bg-slate-500',
                'description' => 'Надёжный профиль с базовыми подтверждениями.',
            ],
            $score >= 40 => [
                'label' => 'Бронзовый уровень',
                'short_label' => 'Бронза',
                'icon' => '🥉',
                'class' => 'border-orange-200 bg-orange-50 text-orange-800',
                'bar' => 'bg-orange-500',
                'description' => 'Базовый уровень доверия, профиль ещё набирает историю.',
            ],
            default => [
                'label' => 'Новый уровень',
                'short_label' => 'Новый',
                'icon' => '●',
                'class' => 'border-indigo-200 bg-indigo-50 text-indigo-800',
                'bar' => 'bg-indigo-500',
                'description' => 'Мало публичных сигналов, стоит проверить профиль вручную.',
            ],
        };

        return [
            ...$tier,
            'score' => $score,
            'signals' => [
                ['label' => 'Email', 'value' => $user->hasVerifiedEmail() ? 'подтверждён' : 'не подтверждён', 'active' => $user->hasVerifiedEmail()],
                ['label' => 'Телефон', 'value' => $user->hasVerifiedPhone() ? 'подтверждён' : 'не подтверждён', 'active' => $user->hasVerifiedPhone()],
                ['label' => 'Сделки', 'value' => $completedOrders . ' завершённых', 'active' => $completedOrders > 0],
                ['label' => 'Отзывы', 'value' => $writtenReviews . ' написано', 'active' => $writtenReviews > 0],
                ['label' => 'Магазин', 'value' => $user->shop ? 'есть' : 'нет', 'active' => (bool) $user->shop],
            ],
        ];
    }

    public function profilesFor(iterable $users): Collection
    {
        return collect($users)
            ->filter()
            ->unique('id')
            ->mapWithKeys(fn (User $user) => [$user->id => $this->profileFor($user)]);
    }
}
