<?php

namespace App\Http\Controllers\API\Accounts;

use App\Actions\Accounts\CloseOwnAccount;
use App\Http\Controllers\Controller;
use App\Services\Otp\OtpException;
use App\Services\Otp\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * "Hapus akun", from inside the app. What is removed and what is kept: see
 * CloseOwnAccount.
 *
 * Confirmed the same way the person signs in — a WhatsApp code to their own
 * number, or their password — so a phone left unlocked on a table is not
 * enough to delete someone.
 */
class MyAccountController extends Controller
{
    public function __construct(
        protected OtpService $otp,
        protected CloseOwnAccount $close,
    ) {}

    /**
     * Whether this account can be deleted here, and how to confirm.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'can_delete' => $this->close->blocker($user) === null,
            'blocker' => $this->close->blocker($user),
            'confirm_with' => $user->phone_normalized ? 'code' : 'password',
            'phone_hint' => $user->phone_normalized ? substr($user->phone_normalized, -4) : null,
            'keeps_rt_record' => $user->residentProfile()->exists(),
        ]);
    }

    /**
     * Sends a confirmation code to the account's own number.
     */
    public function requestCode(Request $request): JsonResponse
    {
        $phone = $request->user()->phone_normalized;

        if (! $phone) {
            return response()->json(['message' => 'Akun ini tidak punya nomor HP. Konfirmasi dengan kata sandi.'], 422);
        }

        try {
            $this->otp->request($phone);
        } catch (RuntimeException) {
            return response()->json(['message' => 'WhatsApp sedang tidak bisa dihubungi. Coba lagi beberapa saat.'], 503);
        }

        return response()->json([
            'message' => 'Kode dikirim lewat WhatsApp.',
            'resend_after' => OtpService::RESEND_AFTER_SECONDS,
        ], 202);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'code' => ['nullable', 'digits:6', 'required_without:password'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);

        if ($blocker = $this->close->blocker($user)) {
            return response()->json(['message' => $blocker], 409);
        }

        if (filled($validated['code'] ?? null)) {
            try {
                $owner = $user->phone_normalized
                    ? $this->otp->verify($user->phone_normalized, $validated['code'])
                    : null;
            } catch (OtpException $exception) {
                return response()->json(['message' => $exception->getMessage()], $exception->status);
            }

            // The code must belong to this very account, not merely to its
            // number.
            if (! $owner || ! $owner->is($user)) {
                return response()->json(['message' => 'Kode tidak cocok dengan akun ini.'], 422);
            }
        } elseif (! Hash::check((string) $validated['password'], (string) $user->password)) {
            return response()->json([
                'message' => 'Kata sandi salah.',
                'errors' => ['password' => ['Kata sandi salah.']],
            ], 422);
        }

        $this->close->handle($user);

        return response()->json(['message' => 'Akun Anda sudah dihapus.']);
    }
}
