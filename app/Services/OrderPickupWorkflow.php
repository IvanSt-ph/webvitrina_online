<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderPickupWorkflow
{
    public function __construct(
        private readonly UserNotificationService $notifications,
        private readonly AdminActivityLogger $activity,
    ) {
    }

    public function sellerUpdate(Order $order, User $seller, string $target, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($order, $seller, $target, $reason): bool {
            $locked = $this->lock($order);
            abort_unless($locked->seller_id === $seller->id && $seller->role === 'seller', 403);

            if ($locked->workflow_version !== Order::WORKFLOW_PICKUP) {
                return $this->legacySellerUpdate($locked, $seller, $target);
            }

            $this->assertPickupOrder($locked);

            if ($target === Order::STATUS_CANCELED) {
                return $this->cancelPickup($locked, $seller, $reason);
            }

            $previous = $locked->status;
            if ($target === Order::STATUS_PROCESSING) {
                if ($previous === $target) {
                    return false;
                }
                $this->require($previous === Order::STATUS_PENDING, 'Принять можно только новый заказ.');
                $this->write($locked, ['status' => $target, 'accepted_at' => now()]);
                $event = 'order_accepted';
            } elseif ($target === Order::STATUS_READY_FOR_PICKUP) {
                if ($previous === $target) {
                    return false;
                }
                $this->require($previous === Order::STATUS_PROCESSING, 'К выдаче можно подготовить только принятый заказ.');
                $this->write($locked, ['status' => $target, 'ready_for_pickup_at' => now()]);
                $event = 'pickup_ready';
            } else {
                $this->require(false, 'Продавец не может установить этот статус.');
            }

            $this->event($locked, $seller, $event, $previous, $target);
            $this->notifyBuyer($locked, 'order_status_updated', 'Статус заказа изменён');

            return true;
        }, 3);
    }

    public function sellerConfirmPayment(Order $order, User $seller): bool
    {
        return DB::transaction(function () use ($order, $seller): bool {
            $locked = $this->lock($order);
            abort_unless($locked->seller_id === $seller->id && $seller->role === 'seller', 403);
            $this->assertPickupOrder($locked);

            if ($locked->payment_status === Order::PAYMENT_SELLER_CONFIRMED && $locked->paid_at !== null) {
                return false;
            }

            $this->require(
                in_array($locked->status, [Order::STATUS_READY_FOR_PICKUP, Order::STATUS_DELIVERED], true)
                    && $locked->payment_status === Order::PAYMENT_UNPAID
                    && $locked->paid_at === null
                    && in_array($locked->payment_method, ['cash', 'card'], true),
                'Оплату можно отметить только при выдаче самовывозного заказа.',
            );

            $this->write($locked, ['payment_status' => Order::PAYMENT_SELLER_CONFIRMED, 'paid_at' => now()]);
            $this->event($locked, $seller, 'payment_seller_confirmed', $locked->status, $locked->status,
                Order::PAYMENT_UNPAID, Order::PAYMENT_SELLER_CONFIRMED);
            $this->completeIfReady($locked);
            $this->notifyBuyer($locked, 'order_payment_recorded', 'Продавец отметил оплату заказа');

            return true;
        }, 3);
    }

    public function buyerConfirmReceipt(Order $order, User $buyer): bool
    {
        return DB::transaction(function () use ($order, $buyer): bool {
            $locked = $this->lock($order);
            abort_unless($locked->user_id === $buyer->id && $buyer->role === 'buyer', 403);

            if ($locked->workflow_version !== Order::WORKFLOW_PICKUP) {
                return $this->legacyBuyerConfirmReceipt($locked, $buyer);
            }

            $this->assertPickupOrder($locked);
            if ($locked->buyer_confirmed_at !== null) {
                $this->require(in_array($locked->status, [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], true),
                    'Состояние подтверждения заказа требует проверки.');

                return false;
            }

            $this->require($locked->status === Order::STATUS_READY_FOR_PICKUP
                && $locked->ready_for_pickup_at !== null
                && in_array($locked->payment_status, [Order::PAYMENT_UNPAID, Order::PAYMENT_SELLER_CONFIRMED], true),
                'Подтверждение получения доступно после готовности заказа к выдаче.');

            $this->write($locked, [
                'status' => Order::STATUS_DELIVERED,
                'buyer_confirmed_at' => now(),
                'delivered_at' => now(),
            ]);
            $this->event($locked, $buyer, 'buyer_receipt_confirmed', Order::STATUS_READY_FOR_PICKUP, Order::STATUS_DELIVERED);
            $this->completeIfReady($locked);
            $this->notifications->create($locked->seller, 'order_delivered', 'Покупатель подтвердил получение',
                "Заказ {$locked->number} отмечен как полученный.", route('seller.orders.show', $locked, false),
                ['order_id' => $locked->id]);

            return true;
        }, 3);
    }

    public function buyerRequestCancellation(Order $order, User $buyer, string $reason): bool
    {
        return DB::transaction(function () use ($order, $buyer, $reason): bool {
            $locked = $this->lock($order);
            abort_unless($locked->user_id === $buyer->id && $buyer->role === 'buyer', 403);
            $reason = trim($reason);
            $this->require($reason !== '' && mb_strlen($reason) <= 700, 'Укажите причину запроса отмены.');

            $allowed = $locked->workflow_version === Order::WORKFLOW_PICKUP
                ? [Order::STATUS_PENDING, Order::STATUS_PROCESSING, Order::STATUS_READY_FOR_PICKUP]
                : [Order::STATUS_PENDING, Order::STATUS_PROCESSING, Order::STATUS_PAID];
            $this->require(in_array($locked->status, $allowed, true)
                && $locked->buyer_confirmed_at === null,
                'Запрос отмены недоступен после получения или завершения заказа.');

            if ($locked->cancellation_requested_at !== null) {
                return false;
            }

            $this->write($locked, [
                'cancellation_requested_at' => now(),
                'cancellation_reason' => $reason,
            ]);
            $this->event($locked, $buyer, 'cancellation_requested', $locked->status, $locked->status,
                metadata: ['reason' => $reason]);
            $this->notifications->create($locked->seller, 'order_cancellation_requested',
                'Покупатель запросил отмену', "По заказу {$locked->number} нужно рассмотреть отмену.",
                route('seller.orders.show', $locked, false), ['order_id' => $locked->id]);

            return true;
        }, 3);
    }

    public function sellerRejectCancellation(Order $order, User $seller, string $reason): bool
    {
        return DB::transaction(function () use ($order, $seller, $reason): bool {
            $locked = $this->lock($order);
            abort_unless($locked->seller_id === $seller->id && $seller->role === 'seller', 403);
            $this->assertPickupOrder($locked);
            $reason = trim($reason);
            $this->require($reason !== '' && mb_strlen($reason) <= 700, 'Укажите причину отклонения отмены.');
            $this->require(! in_array($locked->status, [Order::STATUS_CANCELED, Order::STATUS_COMPLETED], true),
                'Решение по отмене этого заказа уже недоступно.');
            if ($locked->cancellation_requested_at === null) {
                return false;
            }

            // The request's original reason remains in the append-only event.
            $this->write($locked, ['cancellation_requested_at' => null, 'cancellation_reason' => null]);
            $this->event($locked, $seller, 'cancellation_rejected', $locked->status, $locked->status,
                metadata: ['reason' => $reason]);
            $this->notifyBuyer($locked, 'order_cancellation_rejected', 'Продавец отклонил запрос отмены');

            return true;
        }, 3);
    }

    public function adminUpdate(Order $order, User $admin, string $target, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($order, $admin, $target, $reason): bool {
            $locked = $this->lock($order);
            abort_unless($admin->role === 'admin', 403);
            $this->require($locked->workflow_version !== Order::WORKFLOW_PICKUP,
                'Новый заказ самовывоза изменяется только через отдельные подтверждения и решение администратора.');
            $this->require(! in_array($target, [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], true)
                && ! in_array($locked->status, [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], true),
                'Администратор не может подменять подтверждение получения или менять выданный заказ.');
            $this->require(in_array($target, Order::allStatuses(), true), 'Недопустимый статус заказа.');

            if ($locked->status === $target) {
                return false;
            }

            $previous = $locked->status;
            $locked->setStatus($target);
            $this->event($locked, $admin, 'admin_status_changed', $previous, $target,
                metadata: ['reason' => $reason]);
            $this->activity->log('order.status_updated', $locked, 'Администратор изменил статус заказа.', [
                'from' => $previous, 'to' => $target, 'reason' => $reason,
            ]);
            $this->notifyBuyer($locked, 'order_status_updated', 'Администратор изменил статус заказа');

            return true;
        }, 3);
    }

    private function legacySellerUpdate(Order $order, User $seller, string $target): bool
    {
        $allowed = [
            Order::STATUS_PENDING => Order::STATUS_PROCESSING,
            Order::STATUS_PROCESSING => Order::STATUS_PAID,
            Order::STATUS_PAID => Order::STATUS_SHIPPED,
        ];

        if ($target === Order::STATUS_CANCELED && $order->status === Order::STATUS_CANCELED) {
            return false;
        }
        if ($target === $order->status && in_array($target, array_values($allowed), true)) {
            return false;
        }

        $cancelable = [Order::STATUS_PENDING, Order::STATUS_PROCESSING];
        $this->require(($target === Order::STATUS_CANCELED && in_array($order->status, $cancelable, true))
            || ($allowed[$order->status] ?? null) === $target,
            'Исторический заказ нельзя завершить продавцом без подтверждения покупателя. Обратитесь к администратору.');

        $previous = $order->status;
        $order->setStatus($target, $target === Order::STATUS_CANCELED ? $cancelable : [$previous]);
        $this->event($order, $seller, 'legacy_status_changed', $previous, $target);
        $this->notifyBuyer($order, 'order_status_updated', 'Статус заказа изменён');

        return true;
    }

    private function legacyBuyerConfirmReceipt(Order $order, User $buyer): bool
    {
        $this->require(in_array($order->status, [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED], true),
            'Этот заказ ещё не готов к подтверждению получения.');
        if ($order->buyer_confirmed_at !== null) {
            return false;
        }

        $previous = $order->status;
        $order->setStatus(Order::STATUS_DELIVERED, [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED]);
        $this->write($order, ['buyer_confirmed_at' => now()]);
        $this->event($order, $buyer, 'legacy_buyer_receipt_confirmed', $previous, Order::STATUS_DELIVERED);
        $this->notifications->create($order->seller, 'order_delivered', 'Покупатель подтвердил получение',
            "Заказ {$order->number} отмечен как полученный.", route('seller.orders.show', $order, false),
            ['order_id' => $order->id]);

        return true;
    }

    private function cancelPickup(Order $order, User $seller, ?string $reason): bool
    {
        if ($order->status === Order::STATUS_CANCELED) {
            return false;
        }

        $reason = trim((string) $reason);
        $this->require($reason !== '' && mb_strlen($reason) <= 700,
            'Для отмены укажите причину (не более 700 символов).');
        $this->require(in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PROCESSING], true)
            && $order->payment_status === Order::PAYMENT_UNPAID
            && $order->paid_at === null
            && $order->buyer_confirmed_at === null
            && $order->delivered_at === null,
            'После готовности к выдаче, оплаты или получения обычная отмена недоступна. Обратитесь к администратору.');

        $previous = $order->status;
        // Keep the order lock first, then lock products in stable ID order.
        foreach ($order->items()->without('product')->orderBy('product_id')->get()->groupBy('product_id') as $productId => $items) {
            $quantity = (int) $items->sum('quantity');
            if ($items->contains(fn ($item) => $item->quantity <= 0)) {
                throw new \RuntimeException('Некорректное количество товара в заказе.');
            }
            $product = Product::on($order->getConnectionName())->withTrashed()
                ->whereKey($productId)->lockForUpdate()->firstOrFail();
            $product->increment('stock', $quantity);
        }

        $this->write($order, ['status' => Order::STATUS_CANCELED, 'canceled_at' => now()]);
        $this->event($order, $seller, 'order_canceled', $previous, Order::STATUS_CANCELED,
            metadata: ['reason' => $reason]);
        $this->notifyBuyer($order, 'order_status_updated', 'Продавец отменил заказ');

        return true;
    }

    private function completeIfReady(Order $order): void
    {
        if ($order->status !== Order::STATUS_DELIVERED
            || $order->buyer_confirmed_at === null
            || $order->payment_status !== Order::PAYMENT_SELLER_CONFIRMED
            || $order->paid_at === null) {
            return;
        }

        $this->write($order, ['status' => Order::STATUS_COMPLETED, 'completed_at' => now()]);
        $this->event($order, null, 'order_completed', Order::STATUS_DELIVERED, Order::STATUS_COMPLETED);
    }

    private function assertPickupOrder(Order $order): void
    {
        $this->require($order->workflow_version === Order::WORKFLOW_PICKUP
            && $order->delivery_method === 'pickup'
            && in_array($order->payment_method, ['cash', 'card'], true),
            'Этот заказ не относится к новому сценарию самовывоза.');
    }

    private function lock(Order $order): Order
    {
        return Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
    }

    private function write(Order $order, array $values): void
    {
        DB::table('orders')->where('id', $order->id)->update($values + ['updated_at' => now()]);
        $order->refresh();
    }

    private function event(Order $order, ?User $actor, string $type, ?string $from, ?string $to,
        ?string $fromPayment = null, ?string $toPayment = null, array $metadata = []): void
    {
        $order->events()->create([
            'actor_id' => $actor?->id,
            'actor_role' => $actor?->role ?? 'system',
            'event_type' => $type,
            'from_status' => $from,
            'to_status' => $to,
            'from_payment_status' => $fromPayment,
            'to_payment_status' => $toPayment,
            'metadata' => $metadata ?: null,
        ]);
    }

    private function notifyBuyer(Order $order, string $type, string $title): void
    {
        $this->notifications->create($order->user, $type, $title,
            "Заказ {$order->number}: {$order->status_ru}.", route('orders.show', $order, false),
            ['order_id' => $order->id, 'status' => $order->status]);
    }

    private function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }
}
