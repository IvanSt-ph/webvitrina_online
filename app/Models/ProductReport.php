<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReport extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';

    public const ACTION_REVIEWED = 'reviewed';
    public const ACTION_PRODUCT_HIDDEN = 'product_hidden';
    public const ACTION_PRODUCT_RESTORED = 'product_restored';
    public const ACTION_DISMISSED = 'dismissed';

    protected $fillable = [
        'product_id',
        'user_id',
        'reviewed_by',
        'status',
        'reason',
        'details',
        'resolution',
        'action_taken',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN,
            self::STATUS_RESOLVED,
            self::STATUS_DISMISSED,
        ];
    }

    public static function actions(): array
    {
        return [
            self::ACTION_REVIEWED,
            self::ACTION_PRODUCT_HIDDEN,
            self::ACTION_PRODUCT_RESTORED,
            self::ACTION_DISMISSED,
        ];
    }

    public static function actionLabel(?string $action): string
    {
        return match ($action) {
            self::ACTION_PRODUCT_HIDDEN => 'товар заблокирован',
            self::ACTION_PRODUCT_RESTORED => 'товар возвращён',
            self::ACTION_DISMISSED => 'жалоба отклонена',
            self::ACTION_REVIEWED => 'принято к работе',
            default => 'нет действия',
        };
    }
}
