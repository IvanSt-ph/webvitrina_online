<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;

class SellerPlanService
{
    public const STARTER = 'starter';
    public const BASIC = 'basic';
    public const PRO = 'pro';
    public const BUSINESS = 'business';
    public const ENTERPRISE = 'enterprise';

    public function plans(): array
    {
        return [
            self::STARTER => [
                'label' => 'Starter',
                'limit' => 10,
                'class' => 'border-slate-200 bg-slate-50 text-slate-700',
                'description' => 'Бесплатно: базовые функции и стартовый лимит товаров.',
            ],
            self::BASIC => [
                'label' => 'Basic',
                'limit' => 25,
                'class' => 'border-amber-200 bg-amber-50 text-amber-800',
                'description' => 'Больше доверия и немного продвижения для растущего магазина.',
            ],
            self::PRO => [
                'label' => 'Pro',
                'limit' => 50,
                'class' => 'border-cyan-200 bg-cyan-50 text-cyan-800',
                'description' => 'Приоритет в поиске и аналитика для активного продавца.',
            ],
            self::BUSINESS => [
                'label' => 'Business',
                'limit' => 100,
                'class' => 'border-violet-200 bg-violet-50 text-violet-800',
                'description' => 'Выделение магазина и рекламные возможности.',
            ],
            self::ENTERPRISE => [
                'label' => 'Enterprise',
                'limit' => null,
                'class' => 'border-indigo-200 bg-indigo-50 text-indigo-800',
                'description' => 'Unlimited: персональные условия, API, менеджер и премиум поддержка.',
            ],
        ];
    }

    public function allowedKeys(): array
    {
        return array_keys($this->plans());
    }

    public function profileFor(User $seller): array
    {
        $planKey = in_array($seller->seller_plan, $this->allowedKeys(), true)
            ? $seller->seller_plan
            : self::STARTER;

        $plan = $this->plans()[$planKey];
        $used = Product::where('user_id', $seller->id)->count();
        $limit = $plan['limit'];
        $remaining = $limit === null ? null : max(0, $limit - $used);
        $percent = $limit === null ? 100 : min(100, (int) round(($used / max(1, $limit)) * 100));

        return [
            ...$plan,
            'key' => $planKey,
            'used' => $used,
            'remaining' => $remaining,
            'percent' => $percent,
            'limit_label' => $limit === null ? 'unlimited' : (string) $limit,
            'can_create' => $limit === null || $used < $limit,
        ];
    }

    public function canCreateProduct(User $seller): bool
    {
        return $this->profileFor($seller)['can_create'];
    }

    public function limitMessage(User $seller): string
    {
        $profile = $this->profileFor($seller);

        return "Лимит статуса {$profile['label']} исчерпан: {$profile['used']} из {$profile['limit_label']} товаров. Обратитесь в поддержку или к администратору для повышения статуса.";
    }
}
