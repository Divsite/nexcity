<?php

namespace App\Support;

use App\Models\Users\User;
use App\Services\Authorization\CapabilityResolver;

/**
 * The passwords this project handed out instead of asking for one.
 *
 * Residents created by an RT with no password got `password`
 * (ResidentController), and the seeders give every account `password` or —
 * per docs/operations/seeders-and-demo-data.md — `passworrd`. With the API
 * accepting a phone number as the login, knowing someone's number was enough
 * to sign in as them.
 *
 * Compared as plain text at login, where the submitted password is in hand:
 * no extra hashing, and nothing to keep in sync with the stored hash.
 */
final class DefaultPassword
{
    public const KNOWN = ['password', 'passworrd'];

    /** Set on a web session signed in with a default password. */
    public const SESSION_KEY = 'password.must_change';

    public static function is(?string $plain): bool
    {
        return $plain !== null && in_array($plain, self::KNOWN, true);
    }

    /**
     * A warga with no job anywhere: they can sign in with a WhatsApp code
     * instead, so a default password is simply refused for them. Anyone with
     * a job keeps their account but must set a real password first.
     */
    public static function belongsToWargaOnly(User $user): bool
    {
        if ($user->hasRole('superadmin')) {
            return false;
        }

        $memberships = $user->organizationMemberships()->get();
        $resolved = app(CapabilityResolver::class)->resolveByOrganization($user, $memberships);

        foreach ($resolved as $entry) {
            if (($entry['capabilities'] ?? []) !== []) {
                return false;
            }
        }

        // Staff roles without a level still have a job to do on the web.
        return ! $user->hasAnyRole(['mosque_admin', 'rt_admin']);
    }
}
