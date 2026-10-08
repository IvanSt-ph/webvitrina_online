<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Support\PublicImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderIdentitySnapshot
{
    /** Caller holds the product row lock inside the order transaction. */
    public function capture(?Product $product, string $source = 'checkout'): array
    {
        if (DB::transactionLevel() < 1) {
            throw new \LogicException('Order identity capture requires a transaction.');
        }
        $path = PublicImage::path($product?->image);
        $archive = null;
        if ($path && Storage::disk('public')->exists($path)) {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($extension, ['webp', 'jpg', 'jpeg', 'png'], true)) {
                $archive = 'order-snapshots/'.Str::uuid().'.'.$extension;
                $files = new ProductImageOperation(app(ImageService::class), DB::connection());
                $files->copy($path, $archive);
            }
        }

        return [
            'product_title' => $product?->title,
            'product_sku' => $product?->sku,
            'product_image_path' => $archive,
            'identity_snapshot_source' => $product ? $source : 'unavailable',
        ];
    }

    /** Explicit maintenance operation, never run automatically by a migration. */
    public function backfill(): void
    {
        OrderItem::without('product')->whereNull('identity_snapshot_source')->orderBy('id')
            ->chunkById(100, function ($items) {
                foreach ($items as $candidate) {
                    app(BackupWriteBarrier::class)->transaction(function () use ($candidate) {
                        $product = Product::withTrashed()->whereKey($candidate->product_id)->lockForUpdate()->first();
                        $item = OrderItem::without('product')->whereKey($candidate->id)->lockForUpdate()->first();
                        if (! $item || $item->identity_snapshot_source !== null) {
                            return;
                        }
                        // Query-builder write intentionally initializes legacy immutable columns once.
                        DB::table('order_items')->where('id', $item->id)->whereNull('identity_snapshot_source')
                            ->update($this->capture($product, 'legacy_backfill'));
                    });
                }
            });

        DB::table('orders')->whereNull('seller_snapshot')->orderBy('id')->chunkById(100, function ($orders) {
            foreach ($orders as $order) {
                $seller = DB::table('users')->where('id', $order->seller_id)->first();
                $shop = DB::table('shops')->where('user_id', $order->seller_id)->first();
                DB::table('orders')->where('id', $order->id)->whereNull('seller_snapshot')->update([
                    'seller_snapshot' => json_encode([
                        'name' => $seller?->name, 'shop_name' => $shop?->name,
                        'source' => 'legacy_backfill',
                    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                ]);
            }
        });
    }
}
