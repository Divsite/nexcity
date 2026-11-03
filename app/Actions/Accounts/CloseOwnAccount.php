<?php

namespace App\Actions\Accounts;

use App\Models\Organizations\OrganizationUser;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * A person deleting their own Nexcity account ("Hapus akun").
 *
 * Required by Google Play for any app where an account can be created, and
 * the warga's right under UU PDP. What it does, and what it cannot do:
 *
 * **Gone:** signing in (every token revoked, password replaced, the phone no
 * longer matches a login), the email and username, the avatar. A
 * self-registered account also loses its name and phone number — nothing else
 * holds them.
 *
 * **Kept:** what organizations recorded about money and aid. A zakat payment,
 * an iuran bill, a patungan line are the mosque's and the RT's books, and a
 * mosque cannot have last year's totals change because one payer left. Those
 * rows keep the name typed at the counter and point at a closed account.
 *
 * **Kept, and the RT's call:** a warga the RT registered stays in the RT's
 * warga register (name, KK, address) — that is the RT's population record,
 * not an app account. Closing the account only means the warga no longer
 * signs in. Removing them from the register is the RT's decision, on request.
 *
 * Refused for anyone still holding a pengurus level: an organization must not
 * lose its treasurer to a tap. They are removed from the level first.
 */
class CloseOwnAccount
{
    public function blocker(User $user): ?string
    {
        if ($user->hasRole('superadmin')) {
            return 'Akun pemilik platform tidak bisa dihapus dari aplikasi.';
        }

        $isStaff = OrganizationUser::query()
            ->where('user_id', $user->id)
            ->whereNotNull('level_slug')
            ->exists();

        return $isStaff
            ? 'Anda masih tercatat sebagai pengurus. Minta organisasi Anda melepas jabatan itu dulu, lalu hapus akun.'
            : null;
    }

    public function handle(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->tokens()->delete();

            $selfRegistered = $user->registered_via === 'self' && ! $user->residentProfile()->exists();

            $user->forceFill([
                'username' => 'closed.' . $user->id . '.' . Str::lower(Str::random(6)),
                'email' => 'closed.' . $user->id . '.' . Str::lower(Str::random(6)) . '@nexcity.local',
                'password' => Hash::make(Str::random(64)),
                'avatar' => null,
                'closed_at' => now(),
                // Nothing else holds a self-registered person's name and
                // number. A warga the RT registered keeps both on the RT's
                // record; see the class comment.
                ...($selfRegistered ? ['name' => 'Akun dihapus', 'phone' => null] : []),
            ])->save();

            // The saving hook derives this from `phone`; cleared regardless so
            // a WhatsApp code to that number no longer reaches this account.
            DB::table('users')->where('id', $user->id)->update(['phone_normalized' => null]);
        });
    }
}
