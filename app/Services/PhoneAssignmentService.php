<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\User;
use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PhoneAssignmentService
{
    private const LOCK_PREFIX = 'wv:ph:';
    private const LOCK_HASH_LENGTH = 58;
    private const LOCK_TIMEOUT_SECONDS = 5;

    public function __construct(private readonly PhoneNormalizer $normalizer)
    {
    }

    public function normalize(?string $phone): ?string
    {
        return $this->normalizer->normalize($phone);
    }

    public function assignToUser(User $user, ?string $phone): bool
    {
        return $this->updateUser($user, $phone);
    }

    public function updateUser(User $user, ?string $phone, array $attributes = []): bool
    {
        return $this->run($phone, function (?string $normalizedPhone) use ($user, $attributes): bool {
            $current = User::withTrashed()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if ($current->phone === $normalizedPhone) {
                $current->fill($attributes)->save();

                return false;
            }

            $this->assertAvailableForUser($normalizedPhone, $current->id, $current->id);
            $current->fill($attributes)->forceFill([
                'phone' => $normalizedPhone,
                'phone_verified_at' => null,
                'phone_verification_code' => null,
            ])->save();

            return true;
        });
    }

    public function assignToShop(Shop $shop, ?string $phone): bool
    {
        return $this->updateShop($shop, $phone);
    }

    public function updateShop(Shop $shop, ?string $phone, array $attributes = []): bool
    {
        return $this->run($phone, function (?string $normalizedPhone) use ($shop, $attributes): bool {
            $current = Shop::whereKey($shop->getKey())->lockForUpdate()->firstOrFail();

            if ($current->phone === $normalizedPhone) {
                $current->fill($attributes)->save();

                return false;
            }

            $this->assertAvailableForShop($normalizedPhone, $current->user_id, $current->id);
            $current->fill($attributes)->forceFill([
                'phone' => $normalizedPhone,
                'phone_verified_at' => null,
                'phone_verification_code' => null,
                'phone_verification_expires_at' => null,
            ])->save();

            return true;
        });
    }

    public function run(?string $phone, Closure $operation): mixed
    {
        $normalizedPhone = $this->normalizer->normalize($phone);
        $connection = DB::connection();
        $lockName = $normalizedPhone === null ? null : $this->lockName($normalizedPhone);
        $lockAcquired = false;

        try {
            if ($lockName !== null) {
                $lockAcquired = $this->acquireLock($connection, $lockName);

                if (! $lockAcquired) {
                    throw ValidationException::withMessages([
                        'phone' => 'Не удалось безопасно изменить телефон. Повторите попытку.',
                    ]);
                }
            }

            return $connection->transaction(
                fn () => $operation($normalizedPhone, $this),
            );
        } catch (QueryException $exception) {
            if ($this->isPhoneDuplicate($exception)) {
                throw ValidationException::withMessages([
                    'phone' => 'Этот телефон уже используется.',
                ]);
            }

            throw $exception;
        } finally {
            if ($lockAcquired && $lockName !== null) {
                $this->releaseLock($connection, $lockName);
            }
        }
    }

    public function assertAvailableForUser(?string $phone, int $ownerUserId, ?int $userId = null): void
    {
        if ($phone === null) {
            return;
        }

        $usedByAnotherUser = User::withTrashed()
            ->where('phone', $phone)
            ->when($userId !== null, fn ($query) => $query->whereKeyNot($userId))
            ->exists();
        $usedByAnotherOwnersShop = Shop::where('phone', $phone)
            ->where('user_id', '!=', $ownerUserId)
            ->exists();

        if ($usedByAnotherUser || $usedByAnotherOwnersShop) {
            throw ValidationException::withMessages([
                'phone' => 'Этот телефон уже используется другим пользователем или магазином.',
            ]);
        }
    }

    public function assertAvailableForShop(?string $phone, int $ownerUserId, ?int $shopId = null): void
    {
        if ($phone === null) {
            return;
        }

        $usedByAnotherShop = Shop::where('phone', $phone)
            ->when($shopId !== null, fn ($query) => $query->whereKeyNot($shopId))
            ->exists();
        $usedByAnotherUser = User::withTrashed()
            ->where('phone', $phone)
            ->whereKeyNot($ownerUserId)
            ->exists();

        if ($usedByAnotherShop || $usedByAnotherUser) {
            throw ValidationException::withMessages([
                'phone' => 'Этот телефон уже используется другим пользователем или магазином.',
            ]);
        }
    }

    public function lockName(string $normalizedPhone): string
    {
        return self::LOCK_PREFIX . substr(hash('sha256', $normalizedPhone), 0, self::LOCK_HASH_LENGTH);
    }

    private function acquireLock(ConnectionInterface $connection, string $lockName): bool
    {
        $result = $connection->selectOne(
            'SELECT GET_LOCK(?, ?) AS acquired',
            [$lockName, self::LOCK_TIMEOUT_SECONDS],
        );

        return (int) ($result->acquired ?? 0) === 1;
    }

    private function releaseLock(ConnectionInterface $connection, string $lockName): void
    {
        $result = $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);

        if ((int) ($result->released ?? 0) !== 1) {
            Log::critical('Phone advisory lock could not be released.', [
                'lock_name' => $lockName,
            ]);
        }
    }

    private function isPhoneDuplicate(QueryException $exception): bool
    {
        if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
            return false;
        }

        return str_contains($exception->getMessage(), 'users_phone_unique')
            || str_contains($exception->getMessage(), 'shops_phone_unique');
    }
}
