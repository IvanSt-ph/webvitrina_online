<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserSettingsController extends Controller
{
    public function updateCurrency(Request $request)
    {
        $data = $request->validate([
            'currency' => ['required', 'in:PRB,RUB,MDL,UAH'],
        ]);

        $currency = strtoupper($data['currency']) === 'RUB' ? 'PRB' : strtoupper($data['currency']);

        $request->user()->forceFill(['preferred_currency' => $currency])->save();
        session(['currency' => $currency]);

        return back()->with('success', 'Валюта сохранена.');
    }

    public function updateLanguage(Request $request)
    {
        $data = $request->validate([
            'locale' => ['required', 'in:ru,en,uk,ro'],
        ]);

        $request->user()->forceFill(['locale' => $data['locale']])->save();
        session(['locale' => $data['locale']]);

        return back()->with('success', 'Язык интерфейса сохранён.');
    }

    public function updateNotifications(Request $request)
    {
        $data = $request->validate([
            'email_orders' => ['nullable', 'boolean'],
            'email_messages' => ['nullable', 'boolean'],
            'email_reviews' => ['nullable', 'boolean'],
            'email_security' => ['nullable', 'boolean'],
            'site_orders' => ['nullable', 'boolean'],
            'site_messages' => ['nullable', 'boolean'],
            'site_reviews' => ['nullable', 'boolean'],
            'site_support' => ['nullable', 'boolean'],
        ]);

        $request->user()->forceFill([
            'notification_preferences' => [
                'email_orders' => (bool) ($data['email_orders'] ?? false),
                'email_messages' => (bool) ($data['email_messages'] ?? false),
                'email_reviews' => (bool) ($data['email_reviews'] ?? false),
                'email_security' => (bool) ($data['email_security'] ?? false),
                'site_orders' => (bool) ($data['site_orders'] ?? false),
                'site_messages' => (bool) ($data['site_messages'] ?? false),
                'site_reviews' => (bool) ($data['site_reviews'] ?? false),
                'site_support' => (bool) ($data['site_support'] ?? false),
            ],
        ])->save();

        return back()->with('success', 'Настройки уведомлений сохранены.');
    }
}
