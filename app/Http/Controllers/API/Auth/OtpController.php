<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\SessionResource;
use App\Services\Authorization\CapabilityResolver;
use App\Services\Otp\OtpException;
use App\Services\Otp\OtpService;
use App\Support\IndonesianPhone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Signing in with a WhatsApp code: see OtpService for the rules.
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
            'message' => 'Kalau nomor ini terdaftar, kode masuk dikirim lewat WhatsApp.',
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

        $token = $user->createToken('mobile-app')->plainTextToken;

        // Exactly what /auth/login returns, so the app reads one shape.
        return response()->json(
            (new SessionResource($user, $this->capabilities))->resolve($request)
                + ['access_token' => $token, 'token_type' => 'Bearer']
        );
    }

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
