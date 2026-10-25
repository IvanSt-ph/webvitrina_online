<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Observers\ReviewObserver;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountDeletionService
{
    // Called only by User::delete(), under its transaction and row lock.
    public function anonymize(User $user): void
    {
        // The caller holds User; checkout locks both participants before creating orders.
        // Use a current read so a checkout committed while deletion waited is visible.
        $activeOrder = Order::query()
            ->where(fn ($query) => $query->where('user_id', $user->id)->orWhere('seller_id', $user->id))
            ->whereNotIn('status', [Order::STATUS_COMPLETED, Order::STATUS_CANCELED])
            ->where(fn ($query) => $query->whereNotNull('workflow_version')
                ->orWhere('status', '!=', Order::STATUS_DELIVERED)
                ->orWhereNull('buyer_confirmed_at'))
            ->orderBy('id')->lockForUpdate()->first(['id']);
        if ($activeOrder) {
            throw ValidationException::withMessages([
                'user' => 'Нельзя удалить аккаунт, пока у вас есть незавершённые заказы как у покупателя или продавца. Сначала завершите или отмените их.',
            ]);
        }

        $publicFiles = array_filter([$user->avatar, $user->shop?->banner]);
        $reviewFiles = [];
        $conversations = Conversation::where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
        // User is already locked. Freeze owned conversations before taking a
        // current read of attachments; sending takes User -> Conversation too.
        $conversationIds = $conversations->orderBy('id')->lockForUpdate()->pluck('id');
        $messages = Message::where(fn ($query) => $query->whereIn('conversation_id', $conversationIds)->orWhere('sender_id', $user->id));
        // Authored messages in other conversations cannot grow while this User
        // is locked. Lock Message last; do not acquire more Conversation locks.
        $privateFiles = $messages->orderBy('id')->lockForUpdate()->get(['id', 'image_path'])->pluck('image_path')->filter();
        foreach ($privateFiles->unique() as $path) {
            ChatImageOperation::deleteAfterCommit(DB::connection(), $path);
        }
        Message::where('sender_id', $user->id)->delete();
        Conversation::whereKey($conversationIds)->delete();

        Review::withTrashed()->where('user_id', $user->id)->get()->each(function (Review $review) use (&$reviewFiles) {
            $reviewFiles = array_merge($reviewFiles, $review->images()->pluck('path')->all());
            // File removal waits for commit; preserve rating/cache updates without file hooks.
            Review::withTrashed()->whereKey($review->id)->forceDelete();
            app(ReviewObserver::class)->deleted($review);
            if ($review->product) {
                ProductRepository::clearProductCache($review->product);
            }
        });

        foreach (['cart_items', 'favorites', 'shop_followers', 'user_notifications', 'user_remembered_devices', 'seller_plan_requests'] as $table) {
            DB::table($table)->where('user_id', $user->id)->delete();
        }
        // Order-owned snapshots preserve delivery history independently of the address book.
        $user->addresses()->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        if (config('session.driver') === 'database' && (config('session.connection') || config('session.table') !== 'sessions')) {
            DB::connection(config('session.connection'))->table(config('session.table'))->where('user_id', $user->id)->delete();
        }
        if ($user->email) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        }

        // Keep ordered products and stock intact; withdraw the seller's catalogue.
        $user->products()->withTrashed()->each(function (Product $product) use (&$publicFiles, &$reviewFiles) {
            ProductRepository::clearProductCache($product);
            if ($product->orderItems()->exists()) {
                $product->update(['status' => Product::STATUS_BLOCKED]);
                return;
            }

            $publicFiles = array_merge($publicFiles, array_filter([$product->image]), (array) $product->gallery);
            $product->reviews()->withTrashed()->with('images')->get()->each(function (Review $review) use (&$reviewFiles) {
                $reviewFiles = array_merge($reviewFiles, $review->images->pluck('path')->all());
            });
            $product->forceDelete();
        });
        $user->shop?->delete();

        $user->forceFill([
            'name' => 'Удалённый аккаунт',
            'email' => null,
            'phone' => null,
            'avatar' => null,
            'provider' => null,
            'provider_id' => null,
            'email_verified_at' => null,
            'phone_verified_at' => null,
            'phone_verification_code' => null,
            'password' => Str::random(64),
            'password_set_at' => null,
            'remember_token' => null,
            'notification_preferences' => null,
        ])->save();

        DB::afterCommit(function () use ($publicFiles, $reviewFiles) {
            foreach (array_unique($publicFiles) as $file) {
                try {
                    app(ImageService::class)->delete($file, throwOnFailure: true);
                } catch (\Throwable $exception) {
                    \Illuminate\Support\Facades\Log::error('Account file cleanup failed; retry required', [
                        'path' => $file, 'error' => $exception->getMessage(),
                    ]);
                }
            }
            foreach (array_unique($reviewFiles) as $file) {
                try {
                    app(ReviewImageStorage::class)->deleteIfUnreferenced($file, throwOnFailure: true);
                } catch (\Throwable $exception) {
                    \Illuminate\Support\Facades\Log::error('Account file cleanup failed; retry required', [
                        'path' => $file, 'error' => $exception->getMessage(),
                    ]);
                }
            }
        });
    }
}
