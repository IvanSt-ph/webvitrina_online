<?php

namespace App\Support;

final class NotificationPreferences
{
    public const DEFAULTS = [
        'site_orders' => true, 'site_messages' => true,
        'site_reviews' => true, 'site_support' => true,
        'email_orders' => false, 'email_messages' => false, 'email_reviews' => false,
    ];

    public const EVENTS = [
        'order_created' => 'orders', 'order_status_updated' => 'orders',
        'order_payment_recorded' => 'orders', 'order_delivered' => 'orders',
        'order_cancellation_requested' => 'orders', 'order_cancellation_rejected' => 'orders',
        'pickup_confirmation_requested' => 'orders',
        'chat_message' => 'messages',
        'review_approved' => 'reviews', 'review_rejected' => 'reviews',
        'support_message' => 'support',
        'order_dispute_opened' => 'support', 'order_dispute_resolved' => 'support',
        'product_report' => 'support', 'product_report_resolved' => 'support',
        'product_report_product_hidden' => 'support', 'product_report_dismissed' => 'support',
        'product_hidden_by_report' => 'support', 'product_unblocked_by_admin' => 'support',
    ];

    public static function enabled(array $preferences, string $channel, string $type): bool
    {
        $category = self::EVENTS[$type] ?? throw new \InvalidArgumentException("Unknown notification event: {$type}");
        $key = $channel.'_'.$category;
        // Support currently has only a site channel, not an orders/messages email switch.
        return array_key_exists($key, self::DEFAULTS)
            && (bool) ($preferences[$key] ?? self::DEFAULTS[$key]);
    }
}
