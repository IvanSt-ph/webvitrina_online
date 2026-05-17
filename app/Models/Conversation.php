<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'product_id',
        'context_key',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
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
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function includes(User $user): bool
    {
        return $this->buyer_id === $user->id || $this->seller_id === $user->id;
    }

    public function otherParticipant(User $user): User
    {
        return $this->buyer_id === $user->id ? $this->seller : $this->buyer;
    }

    public function recentMessages(int $limit = 50)
    {
        return $this->messages()
            ->with('sender')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    public function olderMessagesBefore(int $beforeId, int $limit = 50)
    {
        return $this->messages()
            ->with('sender')
            ->where('id', '<', $beforeId)
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    public function newerMessagesAfter(int $afterId, int $limit = 50)
    {
        return $this->messages()
            ->with('sender')
            ->where('id', '>', $afterId)
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
