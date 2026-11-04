<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\SessionResource;
use App\Models\Users\User;
use App\Services\Authorization\CapabilityResolver;
use App\Services\Otp\OtpException;
use App\Services\Otp\OtpService;
use App\Support\IndonesianPhone;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Signing in — or opening an account — with a WhatsApp code: see OtpService
 * for the rules.
 *
 * One flow for both, so the screen never has to ask "do you have an account?"
 * and never reveals the answer: number → code → either a session, or (only
 * once the code proved the phone) a request for a name.
 */
class OtpController extends Controller
{
    public function __construct(
        protected OtpService $otp,
        protected CapabilityResolver $capabilities,
    ) {
    }

    public function request(Request $request): JsonResponse
    {
        $phone = $this->phone($request);

        try {
            $this->otp->request($phone);
        } catch (RuntimeException) {
            return response()->json(
                ['message' => 'WhatsApp sedang tidak bisa dihubungi. Coba lagi beberapa saat.'],
                503,
            );
        }

        // The same answer whether or not the number has an account.
        return response()->json([
            'message' => 'Kode dikirim lewat WhatsApp.',
            'resend_after' => OtpService::RESEND_AFTER_SECONDS,
        ], 202);
    }

    public function verify(Request $request): JsonResponse
    {
        $phone = $this->phone($request);
        $code = $request->validate(['code' => 'required|digits:6'])['code'];

        try {
            $user = $this->otp->verify($phone, $code);
        } catch (OtpException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->status);
        }

        if (! $user) {
            // The code proved the phone and no account holds it: now, and
            // only now, it is safe to say so. The token carries the proven
            // number to the register call, so it cannot be swapped for
            // another on the way.
            return response()->json([
                'registration_required' => true,
                'registration_token' => Crypt::encryptString(json_encode([
                    'phone' => $phone,
                    'exp' => now()->addMinutes(self::REGISTRATION_TTL_MINUTES)->timestamp,
                ], JSON_THROW_ON_ERROR)),
            ]);
        }

        return $this->session($request, $user);
    }

    /**
     * Opens an account for a number the code just proved.
     *
     * Tier 1 (docs/architecture/system-map.md §8): a name and a phone, no NIK,
     * no KK, no RT. It can follow mosques, join a patungan and see what is
     * linked to it; it is not a warga of any RT until an RT records it as one.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'registration_token' => ['required', 'string', 'max:2048'],
            'name' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        try {
            $payload = json_decode(Crypt::decryptString($validated['registration_token']), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            $payload = null;
        }

        $phone = is_array($payload) && (int) ($payload['exp'] ?? 0) >= now()->timestamp
            ? IndonesianPhone::normalize((string) ($payload['phone'] ?? ''))
            : null;

        if ($phone === null) {
            return response()->json(['message' => 'Waktu pendaftaran habis. Minta kode baru.'], 410);
        }

        // Someone registered this number in the meantime — the same person on
        // a second phone, most likely. Their account is theirs to sign in to,
        // not to duplicate.
        if ($this->otp->accountsFor($phone)->isNotEmpty()) {
            return response()->json(['message' => 'Nomor ini sudah punya akun. Masuk dengan kode WhatsApp.'], 409);
        }

        $username = 'warga.' . Str::lower(Str::random(10));

        $user = User::create([
            'name' => trim($validated['name']),
            'username' => $username,
            // The column is required and unique; the person gave no email and
            // the app never asks for one.
            'email' => $username . '@daftar.nexcity.local',
            'phone' => '0' . substr($phone, 2),
            // Nobody knows it: this account signs in with WhatsApp codes.
            'password' => Hash::make(Str::random(40)),
            'registered_via' => 'self',
        ]);

        return $this->session($request, $user, 201);
    }

    protected function session(Request $request, User $user, int $status = 200): JsonResponse
    {
        $token = $user->createToken('mobile-app')->plainTextToken;

        // Exactly what /auth/login returns, so the app reads one shape.
        return response()->json(
            (new SessionResource($user, $this->capabilities))->resolve($request)
                + ['access_token' => $token, 'token_type' => 'Bearer'],
            $status,
        );
    }

    /** How long a proven number may wait for its name. */
    public const REGISTRATION_TTL_MINUTES = 15;

    protected function phone(Request $request): string
    {
        $raw = $request->validate(['phone' => 'required|string|max:30'])['phone'];
        $phone = IndonesianPhone::normalize($raw);

        if ($phone === null) {
            throw ValidationException::withMessages([
                'phone' => 'Masukkan nomor HP Indonesia, misalnya 0812 3456 7890.',
            ]);
        }

        return $phone;
    }
}
