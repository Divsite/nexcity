<?php

namespace App\Support;

/**
 * One spelling for an Indonesian mobile number: digits only, starting `62`.
 *
 * Numbers reach `users.phone` in every shape a person can type — `0812…`,
 * `+62 812 …`, `62-812-…` — and partner accounts were formatted with
 * `phone($value, 'MY')`, which turned a typed `0812…` into the Malaysian
 * `+60812…`. Matching a WhatsApp login against that column as written would
 * miss most people, so lookups go through this instead.
 */
final class IndonesianPhone
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        } elseif (str_starts_with($digits, '608')) {
            // The `phone($v, 'MY')` mistake: a typed 0812… became +60812….
            // Malaysian mobiles start +601, so +608 followed by an Indonesian
            // mobile length is the corrupted form, not a real Malaysian number.
            $digits = '62' . substr($digits, 2);
        }

        // 62 + 8 + 8–11 more digits: the length range of Indonesian mobiles.
        if (! preg_match('/^628\d{8,11}$/', $digits)) {
            return null;
        }

        return $digits;
    }
}
