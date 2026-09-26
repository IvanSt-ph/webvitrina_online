<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A failed backfill can be rerun without replacing already captured history.
        if (! Schema::hasColumn('orders', 'address_snapshot')) {
            Schema::table('orders', fn (Blueprint $table) => $table->json('address_snapshot')->nullable());
        }

        DB::table('orders')->whereNull('address_snapshot')->orderBy('id')->chunkById(200, function ($orders) {
            $addresses = DB::table('user_addresses')->whereIn('id', $orders->pluck('address_id')->filter())->get()->keyBy('id');
            foreach ($orders as $order) {
                $address = $addresses->get($order->address_id);
                $snapshot = [];
                foreach (['country', 'city', 'street', 'house', 'entrance', 'apartment', 'postal_code', 'comment'] as $field) {
                    $snapshot[$field] = $address?->$field;
                }
                $parts = array_filter([
                    $snapshot['country'], $snapshot['city'], $snapshot['street'],
                    $snapshot['house'] ? 'д. '.$snapshot['house'] : null,
                    $snapshot['apartment'] ? 'кв. '.$snapshot['apartment'] : null,
                    $snapshot['entrance'] ? 'подъезд '.$snapshot['entrance'] : null,
                    $snapshot['postal_code'] ? '('.$snapshot['postal_code'].')' : null,
                ]);
                // Preserve existing order-local text; lost historical data cannot be reconstructed.
                $snapshot['full'] = $order->delivery_address ?: implode(', ', $parts);
                DB::table('orders')->where('id', $order->id)->whereNull('address_snapshot')
                    ->update(['address_snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Dropping address snapshots would destroy order history. Restore a reviewed backup instead.');
    }
};
