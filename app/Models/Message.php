<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    public function delete()
    {
        if (! $this->exists) {
            return null;
        }

        return app(\App\Services\BackupWriteBarrier::class)->transaction(function () {
            // Neither this path nor Conversation::delete acquires User after
            // Conversation. Account deletion already owns its User lock.
            if (! Conversation::query()->whereKey($this->conversation_id)->lockForUpdate()->first()) {
                return false;
            }
            $current = static::query()->whereKey($this->getKey())
                ->where('conversation_id', $this->conversation_id)->lockForUpdate()->first();
            if (! $current) {
                return false;
            }
            $this->setRawAttributes($current->getAttributes(), true);

            return parent::delete();
        });
    }

    public const TYPE_MESSAGE = 'message';
    public const TYPE_SYSTEM = 'system';
    public const TYPE_INTERNAL_NOTE = 'internal_note';

    protected $fillable = [
        'conversation_id',
        'related_conversation_id',
        'order_id',
        'sender_id',
        'type',
        'body',
        'image_path',
        'read_at',
        'admin_read_at',
    ];

    public function isSystem(): bool
    {
        return $this->type === self::TYPE_SYSTEM;
    }

    public function isInternalNote(): bool
    {
        return $this->type === self::TYPE_INTERNAL_NOTE;
    }

    protected $casts = [
        'read_at' => 'datetime',
        'admin_read_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function relatedConversation()
    {
        return $this->belongsTo(Conversation::class, 'related_conversation_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
