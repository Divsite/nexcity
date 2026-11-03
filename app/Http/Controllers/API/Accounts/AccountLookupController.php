<?php

namespace App\Http\Controllers\API\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Profiles\UserResidentProfile;
use App\Models\Users\User;
use App\Support\AccountRef;
use App\Support\IndonesianPhone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "Akun warga" on the counter forms: finding the account of the person paying,
 * so the transaction shows up in their own app afterwards.
 *
 * Three ways in, all of them things the person in front of the officer hands
 * over themselves: their phone number, their kartu warga QR, or — for the
 * officer paying for themselves — nothing at all.
 *
 * What comes back is deliberately thin. A shortened name to confirm out loud,
 * the last digits of the number, and a sealed reference to put on the form.
 * No id, no address, no NIK: typing a number must not turn into a directory.
 * POST rather than GET so the number never lands in an access log.
 */
class AccountLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:32', 'required_without_all:qr_token,self'],
            'qr_token' => ['nullable', 'string', 'max:64'],
            'self' => ['nullable', 'boolean'],
        ]);

        $organizationId = (int) $request->attributes->get('active_organization_id');

        if ($validated['self'] ?? false) {
            return $this->found($request->user(), $organizationId, $request->user());
        }

        if (filled($validated['qr_token'] ?? null)) {
            $user = UserResidentProfile::query()
                ->with('user')
                ->where('qr_token', $validated['qr_token'])
                ->first()?->user;

            return $user
                ? $this->found($user, $organizationId, $request->user())
                : response()->json(['status' => 'not_found']);
        }

        $phone = IndonesianPhone::normalize($validated['phone'] ?? null);

        if ($phone === null) {
            return response()->json([
                'message' => 'Nomor HP tidak valid.',
                'errors' => ['phone' => ['Nomor HP tidak valid.']],
            ], 422);
        }

        $matches = User::query()->where('phone_normalized', $phone)->limit(2)->get();

        return match ($matches->count()) {
            0 => response()->json(['status' => 'not_found']),
            // Two accounts on one number: picking either would put the money
            // in the wrong person's history. The pengurus records it by name.
            1 => $this->found($matches->first(), $organizationId, $request->user()),
            default => response()->json(['status' => 'ambiguous']),
        };
    }

    protected function found(User $user, int $organizationId, User $officer): JsonResponse
    {
        return response()->json([
            'status' => 'found',
            'account' => [
                'ref' => AccountRef::issue($user, $organizationId),
                'name' => AccountRef::displayName((string) $user->name),
                'phone_hint' => $user->phone_normalized
                    ? substr($user->phone_normalized, -4)
                    : null,
                'is_self' => $user->is($officer),
            ],
        ]);
    }
}
