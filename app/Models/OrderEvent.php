<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'order_id', 'actor_id', 'actor_role', 'event_type',
        'from_status', 'to_status', 'from_payment_status', 'to_payment_status',
        'event_key', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];

    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new \LogicException('Order events are append-only.');
        }

        return parent::save($options);
    }

    public function delete()
    {
        throw new \LogicException('Order events are append-only.');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
