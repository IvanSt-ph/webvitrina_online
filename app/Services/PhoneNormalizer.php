<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class PhoneNormalizer
{
    public function normalize(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $formatted = trim($phone);

        if (preg_match('/[^0-9+\s()\-]/u', $formatted) === 1) {
            $this->invalidPhone();
        }

        $normalized = preg_replace('/[\s()\-]+/u', '', $formatted);

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
