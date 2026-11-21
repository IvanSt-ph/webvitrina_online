<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class PhoneNormalizer
{
    public const LOGIN_COUNTRIES = [
        'md' => ['label' => 'Молдова (+373)', 'code' => '373', 'length' => 8, 'trunk' => '0'],
        'ua' => ['label' => 'Украина (+380)', 'code' => '380', 'length' => 9, 'trunk' => '0'],
        'ro' => ['label' => 'Румыния (+40)', 'code' => '40', 'length' => 9, 'trunk' => '0'],
        'ru' => ['label' => 'Россия (+7)', 'code' => '7', 'length' => 10, 'trunk' => '8'],
    ];

    public function normalize(?string $phone, ?string $country = null): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $formatted = trim($phone);

        if (preg_match('/[^0-9+\s()\-]/u', $formatted) === 1) {
            $this->invalidPhone();
        }

        $normalized = preg_replace('/[\s()\-]+/u', '', $formatted);

        if ($country !== null && ! array_key_exists($country, self::LOGIN_COUNTRIES)) {
            $this->invalidPhone();
        }
        // An explicit international prefix always takes precedence over the selected country.
        if ($normalized !== null && str_starts_with($normalized, '00')) {
            $normalized = '+'.substr($normalized, 2);
        }
        if ($country !== null && $normalized !== null && ! str_starts_with($normalized, '+')) {
            $format = self::LOGIN_COUNTRIES[$country];
            if (strlen($normalized) === strlen($format['code']) + $format['length']
                && str_starts_with($normalized, $format['code'])) {
                $normalized = '+'.$normalized;
            } else {
                if (strlen($normalized) === $format['length'] + 1 && str_starts_with($normalized, $format['trunk'])) {
                    $normalized = substr($normalized, 1);
                }
                if (! ctype_digit($normalized) || strlen($normalized) !== $format['length']) {
                    $this->invalidPhone();
                }
                $normalized = '+'.$format['code'].$normalized;
            }
        }

        if ($normalized !== null && ! str_starts_with($normalized, '+')) {
            $normalized = '+' . $normalized;
        }

        if ($normalized === null || preg_match('/^\+[0-9]{7,15}$/', $normalized) !== 1) {
            $this->invalidPhone();
        }

        return $normalized;
    }

    private function invalidPhone(): never
    {
        throw ValidationException::withMessages([
            'phone' => 'Введите полный номер телефона с кодом страны.',
        ]);
    }
}
