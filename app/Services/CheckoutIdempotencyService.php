<?php

namespace App\Services;

use App\Models\Order;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class CheckoutIdempotencyService
{
    public function result(string $token, int $buyerId): ?array
    {
        return DB::transaction(fn () => $this->readResult($token, $buyerId));
    }

    private function readResult(string $token, int $buyerId): ?array
    {
        $attempt = DB::table('checkout_attempts')->useWritePdo()->where('token_hash', hash('sha256', $token))->lockForUpdate()->first();
        if ($attempt === null) {
            return null;
        }
        if ((int) $attempt->buyer_id !== $buyerId) {
            throw ValidationException::withMessages(['checkout_token' => 'Это оформление принадлежит другому покупателю.']);
        }
        $ids = json_decode($attempt->order_ids, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($ids) || ! array_is_list($ids) || $ids === []
            || array_filter($ids, fn ($id) => ! is_int($id) || $id < 1)
            || count(array_unique($ids)) !== count($ids)) {
            throw new RuntimeException('Invalid persisted checkout result.');
        }
        $orders = Order::query()->useWritePdo()->whereIn('id', $ids)->where('user_id', $buyerId)->orderBy('id')->sharedLock()->get()->keyBy('id');
        if ($orders->count() !== count($ids)) {
            throw new RuntimeException('Persisted checkout orders are unavailable.');
        }
        return array_map(fn ($id) => $orders[$id], $ids);
    }

    public function execute(string $token, int $buyerId, Closure $create): array
    {
        // Only a duplicate of our first INSERT is a replay. Errors in order creation,
        // commit or callbacks must never be interpreted as permission to retry.
        $claimCollision = false;
        try {
            return DB::transaction(function () use ($token, $buyerId, $create, &$claimCollision) {
                try {
                    DB::table('checkout_attempts')->insert([
                        'token_hash' => hash('sha256', $token), 'buyer_id' => $buyerId,
                        'order_ids' => '[]', 'created_at' => now(),
                    ]);
                } catch (UniqueConstraintViolationException $exception) {
                    $claimCollision = true;
                    throw $exception;
                }
                $orders = $create();
                if ($orders === []) {
                    throw new RuntimeException('Checkout must create at least one order.');
                }
                DB::table('checkout_attempts')->where('token_hash', hash('sha256', $token))->update([
                    'order_ids' => json_encode(array_map(fn (Order $order) => (int) $order->id, $orders), JSON_THROW_ON_ERROR),
                ]);
                return $orders;
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (! $claimCollision) {
                throw $exception;
            }
            // A locking read sees the winning commit even under an outer RR snapshot.
            return $this->result($token, $buyerId)
                ?? throw new RuntimeException('Checkout result cannot be resolved.', 0, $exception);
        }
    }
}
