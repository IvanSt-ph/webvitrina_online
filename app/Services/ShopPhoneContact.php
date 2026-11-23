<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShopPhoneContact
{
    /** Read current consent and owner together; never trust cached model relations. */
    public function forShop(Shop $shop): array
    {
        if (! $shop->exists) {
            return ['phone' => null, 'verified' => false, 'public' => false, 'public_phone' => null];
        }
        $row = DB::table('shops')->join('users', 'users.id', '=', 'shops.user_id')
            ->where('shops.id', $shop->getKey())->whereNull('users.deleted_at')
            ->first(['users.phone', 'users.phone_verified_at', 'shops.phone_public']);
        $phone = $row && filled($row->phone) ? $row->phone : null;
        $verified = $this->verified($phone, $row?->phone_verified_at);

        return [
            'phone' => $phone,
            'verified' => $verified,
            'public' => (bool) ($row?->phone_public ?? false),
            'public_phone' => $verified && (bool) $row?->phone_public ? $phone : null,
        ];
    }

    /** Caller holds User then Shop row locks in the same transaction. */
    public function setVisibility(Shop $shop, User $owner, bool $public, ?string $expectedPhone): void
    {
        abort_unless((int) $shop->user_id === (int) $owner->id, 403);
        if ($public && ($owner->trashed() || ! $this->verified($owner->phone, $owner->phone_verified_at)
            || $owner->phone !== $expectedPhone)) {
            throw ValidationException::withMessages([
                'phone_public' => 'Подтвердите актуальный личный номер и обновите страницу перед публикацией.',
            ]);
        }

        $shop->forceFill(['phone_public' => $public]);
    }

    private function verified(?string $phone, mixed $verifiedAt): bool
    {
        return $phone !== null && preg_match('/^\+[0-9]{7,15}$/D', $phone) === 1 && $verifiedAt !== null;
    }
}
