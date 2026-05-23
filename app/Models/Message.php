<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    public const TYPE_MESSAGE = 'message';
    public const TYPE_SYSTEM = 'system';
    public const TYPE_INTERNAL_NOTE = 'internal_note';

    protected $fillable = [
        'conversation_id',
        'related_conversation_id',
        'sender_id',
        'type',
        'body',
        'image_path',
        'read_at',
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
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function relatedConversation()
    {
        return $this->belongsTo(Conversation::class, 'related_conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
