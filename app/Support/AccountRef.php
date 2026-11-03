<?php

namespace App\Support;

use App\Models\Users\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

/**
 * A short-lived, sealed pointer to one warga account, handed to a pengurus at
 * the counter after they looked that warga up.
 *
 * The counter forms could take a bare `user_id`, but then any officer could
 * attach a transaction to any account by guessing numbers, and an account id
 * would be a thing worth harvesting. A reference is only obtainable by looking
 * someone up (their phone number or their kartu warga), only valid in the
 * organization that looked, and only for half an hour: long enough to finish
 * one form, too short to stockpile.
 */
final class AccountRef
{
    public const TTL_MINUTES = 30;

    public static function issue(User $user, int $organizationId): string
    {
        return Crypt::encryptString(json_encode([
            'u' => $user->id,
            'o' => $organizationId,
            'e' => now()->addMinutes(self::TTL_MINUTES)->timestamp,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * The account a reference points to, or a validation error on [$field].
     *
     * @throws ValidationException
     */
    public static function resolve(string $ref, int $organizationId, string $field): User
    {
        try {
            $payload = json_decode(Crypt::decryptString($ref), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            $payload = null;
        }

        $valid = is_array($payload)
            && (int) ($payload['o'] ?? 0) === $organizationId
            && (int) ($payload['e'] ?? 0) >= now()->timestamp;

        $user = $valid ? User::find((int) ($payload['u'] ?? 0)) : null;

        if (! $user) {
            throw ValidationException::withMessages([
                $field => 'Akun warga sudah tidak berlaku. Cari ulang akunnya.',
            ]);
        }

        return $user;
    }

    /**
     * "Citra L." — enough for the officer to confirm with the person standing
     * in front of them, not enough to turn a phone number into a full name.
     */
    public static function displayName(string $name): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];
        $first = array_shift($words) ?? '';

        $initials = array_map(
            fn (string $w) => mb_strtoupper(mb_substr($w, 0, 1)) . '.',
            array_filter($words, fn (string $w) => $w !== ''),
        );

        return trim($first . ' ' . implode(' ', $initials));
    }
}
