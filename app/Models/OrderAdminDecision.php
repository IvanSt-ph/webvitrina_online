<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderAdminDecision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['order_id', 'admin_id', 'decision', 'reason'];

    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new \LogicException('Order admin decisions are append-only.');
        }

        return parent::save($options);
    }

    public function delete()
    {
        throw new \LogicException('Order admin decisions are append-only.');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
