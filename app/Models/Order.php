<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Order extends Model
{
    use HasFactory;

    public function save(array $options = [])
    {
        if ($this->exists && $this->isDirty('address_snapshot')) {
            throw new \LogicException('Order address snapshot is immutable.');
        }
        if ($this->exists && $this->isDirty('seller_snapshot')) {
            throw new \LogicException('Order seller snapshot is immutable.');
        }

        return parent::save($options);
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $seller = User::find($order->seller_id);
            $order->seller_snapshot = [
                'name' => $seller?->name, 'shop_name' => $seller?->shop?->name, 'source' => 'checkout',
            ];
            $buyer = User::findOrFail($order->user_id);
            $order->buyer_contact = $buyer->only(['name', 'email', 'phone']);
            $address = null;
            if ($order->address_id !== null) {
                $address = UserAddress::whereKey($order->address_id)
                    ->where('user_id', $buyer->id)->lockForUpdate()->first();
                if (! $address) {
                    throw ValidationException::withMessages(['address_id' => 'Выберите адрес из своего профиля.']);
                }
            }
            // Always derive from persisted server data, never from a supplied snapshot.
            $order->address_snapshot = $address ? array_merge(
                $address->only(['country', 'city', 'street', 'house', 'entrance', 'apartment', 'postal_code', 'comment']),
                ['full' => $address->full],
            ) : ['full' => $order->delivery_address, 'comment' => null];
        });
    }

    public function getBuyerNameAttribute(): string
    {
        return $this->buyer_contact['name'] ?? 'Покупатель не указан';
    }

    public function getHistoricalSellerNameAttribute(): string
    {
        $name = $this->seller_snapshot['shop_name'] ?? $this->seller_snapshot['name'] ?? 'Продавец не сохранён';
        return ($this->seller_snapshot['source'] ?? null) === 'checkout'
            ? $name : $name.' (данные на момент покупки не подтверждены)';
    }

    public function getBuyerEmailAttribute(): ?string
    {
        return $this->buyer_contact['email'] ?? null;
    }

    public function getBuyerPhoneAttribute(): ?string
    {
        return $this->buyer_contact['phone'] ?? null;
    }

    /* -------------------------------------------------
     | 📌 Статусы
     |--------------------------------------------------*/

    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PAID       = 'paid';
    public const STATUS_SHIPPED    = 'shipped';
    public const STATUS_DELIVERED  = 'delivered';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_CANCELED   = 'canceled';
    // Reserved for the pickup workflow. Existing status transitions stay unchanged in stage 1.
    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';

    public const WORKFLOW_PICKUP = 2;
    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_SELLER_CONFIRMED = 'seller_confirmed';

    public static function allStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_PAID,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELED,
        ];
    }

    public static function paymentMethodLabels(): array
    {
        return [
            'cash' => 'Наличными при получении или передаче товара',
            'card' => 'Картой при получении (онлайн-оплата на сайте пока не выполняется)',
            'bank_transfer' => 'Перевод по согласованию с продавцом',
        ];
    }

    public static function deliveryMethodLabels(): array
    {
        return [
            'courier' => 'Доставка продавцом',
            'pickup' => 'Самовывоз',
            'post' => 'Отправка почтой',
            'express' => 'Экспресс-доставка/такси',
        ];
    }

    public static function generateNumber(): string
    {
        return 'ORD-' . Str::ulid();
    }

    /* -------------------------------------------------
     | 📌 Fillable
     |--------------------------------------------------*/

    protected $fillable = [
        'user_id',
        'seller_id',
        'address_id',
        'number',
        'status',
        'total_price',
        'currency',
        'payment_method',
        'delivery_method',
        'paid_at',
        'delivery_address',

        // новые timestamp-поля
        'accepted_at',
        'shipped_at',
        'delivered_at',
        'canceled_at',
        'cancellation_requested_at',
        'cancellation_reason',
    ];

    /* -------------------------------------------------
     | 📌 Eager loading
     |--------------------------------------------------*/

    protected $casts = [
        'seller_snapshot' => 'array',
        'address_snapshot' => 'array',
        'buyer_contact' => 'array',
        'paid_at' => 'datetime',
        'accepted_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'canceled_at' => 'datetime',
        'cancellation_requested_at' => 'datetime',
        'workflow_version' => 'integer',
        'ready_for_pickup_at' => 'datetime',
        'buyer_confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'confirmation_requested_at' => 'datetime',
    ];
public function markAsPaid(): void
{
    if ($this->status === self::STATUS_PAID) {
        return; // уже оплачено
    }

    $this->setStatus(self::STATUS_PAID);

    // 🔹 Пересчёт продаж по всем товарам в заказе
    foreach ($this->items as $item) {
        $shop = $item->product->shop;
        if ($shop) {
            $shop->incrementSales($item->quantity);
        }
    }
}


    /* -------------------------------------------------
     | 🔗 Отношения
     |--------------------------------------------------*/

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function address()
    {
        return $this->belongsTo(UserAddress::class)->withDefault();
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault(['name' => 'Удалённый аккаунт']);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id')->withDefault(['name' => 'Удалённый аккаунт']);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items')
                    ->withPivot(['quantity', 'price'])
                    ->withTimestamps();
    }

    public function disputes()
    {
        return $this->hasMany(OrderDispute::class);
    }

    public function events()
    {
        return $this->hasMany(OrderEvent::class);
    }

    public function adminDecisions()
    {
        return $this->hasMany(OrderAdminDecision::class);
    }

    public function openDispute()
    {
        return $this->hasOne(OrderDispute::class)->where('status', OrderDispute::STATUS_OPEN);
    }

    /* -------------------------------------------------
     | 🏷 Аксессоры
     |--------------------------------------------------*/

    public function getStatusRuAttribute()
    {
        return match($this->status) {
            'pending'   => 'Ожидает обработки',
            'processing'=> 'Принят продавцом',
            'paid'      => 'Оплачен',
            'shipped'   => 'В пути',
            'delivered' => 'Доставлен',
            'completed' => 'Завершён',
            'canceled'  => 'Отменён',
            'ready_for_pickup' => 'Готов к самовывозу',
            default     => $this->status,
        };
    }

    public function getFormattedTotalPriceAttribute()
    {
        return number_format($this->total_price, 2, ',', ' ') . ' ' . Product::currencySymbol($this->currency);
    }

    public function getItemsSubtotalAttribute(): float
    {
        return round($this->items->sum(fn (OrderItem $item) => (float) $item->total), 2, PHP_ROUND_HALF_UP);
    }

    public function getDeliveryCostAttribute(): float
    {
        return max(0, round((float) $this->total_price - $this->items_subtotal, 2, PHP_ROUND_HALF_UP));
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::paymentMethodLabels()[$this->payment_method] ?? ($this->payment_method ?: 'Не указано');
    }

    public function getDeliveryMethodLabelAttribute(): string
    {
        return self::deliveryMethodLabels()[$this->delivery_method] ?? ($this->delivery_method ?: 'Не указано');
    }

    /* -------------------------------------------------
     | 🔍 Скоупы
     |--------------------------------------------------*/

    public function scopeForSeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /* -------------------------------------------------
     | ⚙️ Логика смены статуса
     |--------------------------------------------------*/

    public function setStatus(string $status, ?array $allowedFrom = null): void
    {
        if (! in_array($status, self::allStatuses(), true)) {
            throw new \InvalidArgumentException("Недопустимый статус заказа: {$status}");
        }

        $order = $this->getConnection()->transaction(function () use ($status, $allowedFrom) {
            // Always read persisted state under the lock, including for stale model instances.
            $order = $this->newQuery()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            if ($order->status === $status) {
                return $order;
            }

            if ($order->status === self::STATUS_CANCELED
                || ($allowedFrom !== null && ! in_array($order->status, $allowedFrom, true))) {
                throw ValidationException::withMessages(['status' => 'Недопустимый переход статуса.']);
            }

            if ($status === self::STATUS_CANCELED) {
                // Include withdrawn products; account deletion must not lose their inventory.
                foreach ($order->items()->without('product')->orderBy('product_id')->get()->groupBy('product_id') as $productId => $items) {
                    $quantity = (int) $items->sum('quantity');
                    if ($items->contains(fn ($item) => $item->quantity <= 0)) {
                        throw new \RuntimeException('Некорректное количество товара в заказе.');
                    }
                    $product = Product::on($order->getConnectionName())->withTrashed()
                        ->whereKey($productId)->lockForUpdate()->firstOrFail();
                    $product->increment('stock', $quantity);
                }
            }

            $order->status = $status;
            $timestamp = match ($status) {
                self::STATUS_PROCESSING => 'accepted_at',
                self::STATUS_SHIPPED => 'shipped_at',
                self::STATUS_DELIVERED => 'delivered_at',
                self::STATUS_CANCELED => 'canceled_at',
                default => null,
            };
            if ($timestamp !== null) {
                $order->$timestamp ??= now();
            }
            $order->save();

            return $order;
        }, 3);

        $this->setRawAttributes($order->getAttributes(), true);
    }


        public function buyer()
        {
            return $this->belongsTo(User::class, 'user_id')->withDefault();
        }


}
