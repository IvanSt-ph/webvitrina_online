<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\MarketplaceEventNotification;
use App\Support\SafeRedirect;
use App\Support\NotificationPreferences;

class UserNotificationService
{
    public function create(?User $user, string $type, string $title, ?string $body = null, ?string $url = null, array $data = []): ?UserNotification
    {
        $notification = $this->createSite($user, $type, $title, $body, $url, $data);
        $this->sendEmail($user, $type, $title, $body, $url);

        return $notification;
    }

    /** Persist on the caller's transaction; never dispatch email here. */
    public function createSite(?User $user, string $type, string $title, ?string $body = null, ?string $url = null, array $data = []): ?UserNotification
    {
        if (! $user || ! $user->exists || $user->trashed() || ! User::whereKey($user->id)->exists()) {
            return null;
        }

        if (! NotificationPreferences::enabled($user->notification_preferences ?? [], 'site', $type)) {
            return null;
        }

        $safeUrl = SafeRedirect::internalPath($url);

        return $user->notifications()->create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $safeUrl,
            'data' => $data ?: null,
        ]);
    }

    public function sendEmail(?User $user, string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        if ($user && $user->exists && ! $user->trashed() && User::whereKey($user->id)->exists() && $this->shouldSendEmail($user, $type)) {
            $user->notify(new MarketplaceEventNotification($title, $body, SafeRedirect::internalPath($url)));
        }
    }

    private function shouldSendEmail(User $user, string $type): bool
    {
        return NotificationPreferences::enabled($user->notification_preferences ?? [], 'email', $type);
    }
}
