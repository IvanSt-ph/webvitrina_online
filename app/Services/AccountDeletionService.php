<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Observers\ReviewObserver;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AccountDeletionService
{
    // Called only by User::delete(), under its transaction and row lock.
    public function anonymize(User $user): void
    {
        $publicFiles = array_filter([$user->avatar, $user->shop?->banner]);
        $reviewFiles = [];
        $conversations = Conversation::where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
        $conversationIds = $conversations->pluck('id');
        $messages = Message::where(fn ($query) => $query->whereIn('conversation_id', $conversationIds)->orWhere('sender_id', $user->id));
        $privateFiles = $messages->whereNotNull('image_path')->pluck('image_path')->all();
        Message::where('sender_id', $user->id)->delete();
        $conversations->delete();

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

        DB::afterCommit(function () use ($publicFiles, $privateFiles, $reviewFiles) {
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
            try {
                if (! Storage::disk('local')->delete($privateFiles)) {
                    throw new \RuntimeException('Cannot delete private account attachments.');
                }
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::error('Account file cleanup failed; retry required', [
                    'paths' => $privateFiles, 'error' => $exception->getMessage(),
                ]);
            }
        });
    }
}
