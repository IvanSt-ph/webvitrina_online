<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    public const TYPE_MARKETPLACE = 'marketplace';
    public const TYPE_SUPPORT = 'support';

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'product_id',
        'context_key',
        'conversation_type',
        'last_message_at',
        'locked_at',
        'locked_by',
        'locked_reason',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Conversation $conversation) {
            if ($conversation->context_key) {
                return;
            }

            $conversation->context_key = $conversation->product_id
                ? 'product:' . $conversation->product_id
                : self::generalContextKey();
        });

        static::deleting(function (Conversation $conversation) {
            $conversation->messages()->each(function (Message $message) {
                $message->delete();
            });
        });
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public static function generalContextKey(): string
    {
        return 'general';
    }

    public static function productContextKey(Product $product): string
    {
        return 'product:' . $product->id;
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)
            ->ofMany(['id' => 'max'], fn ($query) => $query->where(function ($query) {
                $query
                    ->where('type', '!=', Message::TYPE_INTERNAL_NOTE)
                    ->orWhereNull('type');
            }));
    }

    public function includes(User $user): bool
    {
        return $this->buyer_id === $user->id || $this->seller_id === $user->id;
    }

    public function isSupport(): bool
    {
        return $this->conversation_type === self::TYPE_SUPPORT;
    }

    public function isMarketplace(): bool
    {
        return $this->conversation_type === self::TYPE_MARKETPLACE;
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function otherParticipant(User $user): User
    {
        return $this->buyer_id === $user->id ? $this->seller : $this->buyer;
    }

    public function recentMessages(int $limit = 50, bool $includeInternalNotes = false)
    {
        return $this->messages()
            ->with(['sender', 'relatedConversation'])
            ->when(! $includeInternalNotes, fn ($query) => $query->where(function ($query) {
                $query
                    ->where('type', '!=', Message::TYPE_INTERNAL_NOTE)
                    ->orWhereNull('type');
            }))
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    public function olderMessagesBefore(int $beforeId, int $limit = 50, bool $includeInternalNotes = false)
    {
        return $this->messages()
            ->with(['sender', 'relatedConversation'])
            ->where('id', '<', $beforeId)
            ->when(! $includeInternalNotes, fn ($query) => $query->where(function ($query) {
                $query
                    ->where('type', '!=', Message::TYPE_INTERNAL_NOTE)
                    ->orWhereNull('type');
            }))
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    public function newerMessagesAfter(int $afterId, int $limit = 50, bool $includeInternalNotes = false)
    {
        return $this->messages()
            ->with(['sender', 'relatedConversation'])
            ->where('id', '>', $afterId)
            ->when(! $includeInternalNotes, fn ($query) => $query->where(function ($query) {
                $query
                    ->where('type', '!=', Message::TYPE_INTERNAL_NOTE)
                    ->orWhereNull('type');
            }))
            ->oldest('id')
            ->limit($limit)
            ->get();
    }

    public function latestReadOutgoingMessageIdFor(User $user): int
    {
        return (int) $this->messages()
            ->where('sender_id', $user->id)
            ->whereNotNull('read_at')
            ->max('id');
    }

    public function hasMoreThanRecentMessages(int $limit = 50): bool
    {
        return $this->messages()
            ->skip($limit)
            ->take(1)
            ->exists();
    }

    public function hasMessagesBefore(int $beforeId): bool
    {
        return $this->messages()
            ->where('id', '<', $beforeId)
            ->exists();
    }
}
