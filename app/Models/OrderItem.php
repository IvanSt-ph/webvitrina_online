<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    public const IDENTITY_FIELDS = ['product_title', 'product_sku', 'product_image_path', 'identity_snapshot_source'];

    public function save(array $options = [])
    {
        if ($this->exists && $this->isDirty(self::IDENTITY_FIELDS)) {
            throw new \LogicException('Order item identity snapshot is immutable.');
        }
        return parent::save($options);
    }

    public function getHistoricalTitleAttribute(): string
    {
        $title = $this->product_title ?: 'Название не сохранено';
        return $this->identity_snapshot_source === 'checkout'
            ? $title : $title.' (данные на момент покупки не подтверждены)';
    }

    public function getHistoricalImageUrlAttribute(): string
    {
        return \App\Support\PublicImage::url($this->product_image_path);
    }

    public function getHistoricalImageCandidatesAttribute(): array
    {
        return \App\Support\PublicImage::candidates($this->product_image_path);
    }
    protected $fillable = [
        'order_id',
        'product_id',
        'price',
        'quantity',
        'total',
        'source_price',
        'source_currency',
        'exchange_rate',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total' => 'decimal:2',
        'source_price' => 'decimal:2',
        'exchange_rate' => 'decimal:8',
    ];

    // Всегда подгружаем товар (даже soft-deleted)
    protected $with = ['product'];

    /** Заказ */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /** Товар (даже если он soft-deleted) */
        public function product()
        {
            return $this->belongsTo(Product::class)->withTrashed()->with(['category', 'seller']);
        }


    /** Считаем total если вдруг пусто */
    public function getTotalPriceAttribute()
    {
        return $this->quantity * $this->price;
    }
}
