<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class ThrottledCurrentPassword implements ValidationRule
{
    public const MAX_ATTEMPTS = 5;
    public const WINDOW_SECONDS = 60;

    public function __construct(private readonly ?User $currentUser = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = $this->currentUser ?? Auth::guard('web')->user();
        if ($user === null || ! is_string($value)) {
            $fail('Текущий пароль введён неверно.');

            return;
        }

        // One budget across endpoints, sessions and IP addresses. Fixed windows
        // keep success/rejection refunds tied to the original reservation even
        // when a slow password check finishes in the next window.
        $now = now()->getTimestamp();
        $window = intdiv($now, self::WINDOW_SECONDS);
        $key = 'current-password:web:'.$user->getAuthIdentifier().':'.$window;
        $cache = Cache::store(config('cache.limiter'));
        $store = $cache->getStore();
        if ($store instanceof DatabaseStore) {
            // Attempt reservations must survive rollback of the protected write.
            // A separate connection also releases cache locks before hashing.
            $store = clone $store;
            $configuration = $store->getConnection()->getConfig();
            $name = 'current_password_limiter_'.hash('sha256', $store->getConnection()->getName());
            $configuration['name'] = $name;
            config(['database.connections.'.$name => $configuration]);
            $store->setConnection(DB::connection($name));
            $cache = new \Illuminate\Cache\Repository($store);
        }
        // File/local memory stores cannot enforce a shared atomic budget across
        // production workers. Array is allowed only in isolated automated tests.
        if (! ($store instanceof DatabaseStore || $store instanceof RedisStore
            || ($store instanceof ArrayStore && app()->environment('testing')))) {
            throw new \RuntimeException('Current password throttling requires a shared database or Redis cache.');
        }
        $cache->add($key, 0, self::WINDOW_SECONDS * 2);

        // Reserve BEFORE hashing. Atomic increment (database/Redis) prevents
        // concurrent requests from all passing a separate check-then-hit gate.
        $attempts = $cache->increment($key);
        if (! is_numeric($attempts) || $attempts < 1) {
            throw new \RuntimeException('Current password attempt reservation failed.');
        }
        if ($attempts > self::MAX_ATTEMPTS) {
            $cache->decrement($key);
            throw new TooManyRequestsHttpException(
                self::WINDOW_SECONDS - ($now % self::WINDOW_SECONDS),
                'Слишком много попыток подтверждения пароля. Повторите позже.',
            );
        }

        if (! Hash::check($value, $user->getAuthPassword())) {
            $fail('Текущий пароль введён неверно.');

            return;
        }

        // Refund only this successful attempt, never clear other failures.
        $cache->decrement($key);
    }
}
