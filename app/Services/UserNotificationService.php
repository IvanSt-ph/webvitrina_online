<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\MarketplaceEventNotification;
use App\Support\SafeRedirect;

class UserNotificationService
{
    public function create(User $user, string $type, string $title, ?string $body = null, ?string $url = null, array $data = []): UserNotification
    {
        $safeUrl = SafeRedirect::internalPath($url);

        $notification = $user->notifications()->create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $safeUrl,
            'data' => $data ?: null,
        ]);

        if ($this->shouldSendEmail($user, $type)) {
            $user->notify(new MarketplaceEventNotification($title, $body, $safeUrl));
        }

        return $notification;
    }

    private function shouldSendEmail(User $user, string $type): bool
    {
        $preferences = $user->notification_preferences ?? [];

        $key = match (true) {
            str_contains($type, 'message'), str_contains($type, 'chat') => 'email_messages',
            str_contains($type, 'review') => 'email_reviews',
            str_contains($type, 'security'), str_contains($type, 'password') => 'email_security',
            str_contains($type, 'support'), str_contains($type, 'dispute'), str_contains($type, 'report') => 'email_orders',
            default => 'email_orders',
        };

        return (bool) ($preferences[$key] ?? false);
    }
}
