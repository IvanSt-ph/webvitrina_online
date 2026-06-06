<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdCampaign extends Model
{
    public const TYPE_PRODUCT = 'product';
    public const TYPE_SHOP = 'shop';
    public const TYPE_CUSTOM = 'custom';

    protected $fillable = [
        'ad_slot_id',
        'product_id',
        'shop_id',
        'category_id',
        'created_by',
        'updated_by',
        'target_type',
        'title',
        'label',
        'description',
        'destination_url',
        'sort_order',
        'max_impressions',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_impressions' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public static function targetTypes(): array
    {
        return [
            self::TYPE_PRODUCT => 'Товар',
            self::TYPE_SHOP => 'Магазин',
            self::TYPE_CUSTOM => 'Своя ссылка',
        ];
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereHas('slot', fn (Builder $slot) => $slot->where('is_active', true))
            ->where(function (Builder $date) {
                $date->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $date) {
                $date->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->where(function (Builder $limit) {
                $limit->whereNull('max_impressions')
                    ->orWhereRaw('(select count(*) from ad_impressions where ad_impressions.ad_campaign_id = ad_campaigns.id) < ad_campaigns.max_impressions');
            });
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(AdSlot::class, 'ad_slot_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function impressions(): HasMany
    {
        return $this->hasMany(AdImpression::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AdClick::class);
    }

    public function getResolvedUrlAttribute(): string
    {
        if ($this->target_type === self::TYPE_PRODUCT && $this->product) {
            return route('product.show', $this->product->slug ?: $this->product->id);
        }

        if ($this->target_type === self::TYPE_SHOP && $this->shop?->slug) {
            return route('seller.show', $this->shop->slug);
        }

        return $this->destination_url ?: '#';
    }

    public function getTargetNameAttribute(): string
    {
        if ($this->target_type === self::TYPE_PRODUCT) {
            return $this->product?->title ?? 'Товар удалён';
        }

        if ($this->target_type === self::TYPE_SHOP) {
            return $this->shop?->name ?? 'Магазин удалён';
        }

        return $this->destination_url ?: 'Своя ссылка';
    }

    public function getComputedStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'paused';
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'scheduled';
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'finished';
        }

        if ($this->max_impressions !== null && $this->impressions_count !== null && $this->impressions_count >= $this->max_impressions) {
            return 'finished';
        }

        return 'active';
    }

    public function getComputedStatusLabelAttribute(): string
    {
        return match ($this->computed_status) {
            'scheduled' => 'Запланирована',
            'active' => 'Активна',
            'finished' => 'Завершена',
            'paused' => 'Пауза',
            default => 'Черновик',
        };
    }
}
