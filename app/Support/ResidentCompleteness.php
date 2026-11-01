<?php

namespace App\Support;

use App\Models\Profiles\UserResidentProfile;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * What a resident record still lacks, by the measure that matters downstream.
 *
 * The mosque picks zakat and qurban recipients from RT data by KK and address,
 * the WhatsApp login finds people by phone, and field officers scan the card.
 * A record missing any of these is one the mosque cannot serve well — so these
 * four, and only these, count as incomplete.
 */
final class ResidentCompleteness
{
    /** @var array<string, string> key => label shown in the app */
    public const CHECKS = [
        'family_card_number' => 'Nomor KK',
        'address_line' => 'Alamat',
        'phone' => 'Nomor HP',
        'card' => 'Kartu QR',
    ];

    /**
     * @return list<string> keys of CHECKS that are missing
     */
    public static function missing(User $user, UserResidentProfile $profile): array
    {
        return array_values(array_filter([
            blank($profile->family_card_number) ? 'family_card_number' : null,
            blank($profile->address_line) ? 'address_line' : null,
            blank($user->phone_normalized) ? 'phone' : null,
            blank($profile->qr_token) ? 'card' : null,
        ]));
    }

    /**
     * Restricts a users query to residents missing at least one check.
     *
     * Phone counts as missing when it does not normalize: a number the
     * WhatsApp login cannot match is as good as none.
     *
     * @param  Builder<User>  $users
     * @return Builder<User>
     */
    public static function scopeIncomplete(Builder $users): Builder
    {
        return $users->where(function (Builder $query) {
            $query->whereNull('phone_normalized')
                ->orWhereHas('residentProfile', function (Builder $profile) {
                    $profile->where(fn (Builder $p) => $p
                        ->whereNull('family_card_number')->orWhere('family_card_number', ''))
                        ->orWhere(fn (Builder $p) => $p
                            ->whereNull('address_line')->orWhere('address_line', ''))
                        ->orWhereNull('qr_token');
                });
        });
    }

    /** "•••• 1234": enough to check against a printed KK, not enough to copy. */
    public static function maskFamilyCard(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';

        return strlen($digits) >= 4 ? '•••• ' . substr($digits, -4) : null;
    }
}
