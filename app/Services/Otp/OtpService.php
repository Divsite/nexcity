<?php

namespace App\Services\Otp;

use App\Models\Users\PhoneOtp;
use App\Models\Users\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Signing in with a code sent to WhatsApp.
 *
 * Built for warga whose account the RT already created: they activate it with
 * the phone number the RT recorded, and never need a password. Since
 * 4 November 2026 it is also the way in for someone with no account yet: the
 * same code proves the phone, and only then does the app ask for a name
 * (OtpController::register).
 *
 * Two rules shape everything here:
 *
 * 1. **The request step never says whether a number is registered.** Anyone
 *    can type a number; answering "not found" would let them walk the RT's
 *    phone list. A code goes to every valid number, so the answer, the timing
 *    and the WhatsApp message are the same either way. Only after the code is
 *    entered — which proves the person holds that phone — does the answer
 *    depend on the account.
 * 2. **The code is never stored**, only its HMAC.
 */
class OtpService
{
    public const CODE_TTL_MINUTES = 5;

    public const MAX_ATTEMPTS = 5;

    /** A new code is not sent sooner than this after the last one. */
    public const RESEND_AFTER_SECONDS = 60;

    public function __construct(protected WhatsAppSender $sender)
    {
    }

    /**
     * Sends a code to the number, account or not (see rule 1).
     *
     * @throws RuntimeException when WhatsApp could not take the message
     */
    public function request(string $phone): void
    {
        $latest = PhoneOtp::query()->where('phone', $phone)->latest('id')->first();

        if ($latest && $latest->created_at->diffInSeconds(now()) < self::RESEND_AFTER_SECONDS) {
            return;
        }

        $code = (string) random_int(100000, 999999);

        // One live code per number: an older one stops working the moment a
        // new one is sent, so a code read off someone's lock screen earlier
        // cannot be used once they have asked again.
        PhoneOtp::query()
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        PhoneOtp::create([
            'phone' => $phone,
            'code_hash' => $this->hash($phone, $code),
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ]);

        $this->sender->send($phone, implode("\n", [
            "Kode Nexcity: {$code}",
            'Berlaku ' . self::CODE_TTL_MINUTES . ' menit.',
            'Jangan berikan kode ini kepada siapa pun, termasuk pengurus.',
        ]));
    }

    /**
     * Checks the code and returns the account it signs in to — or null when
     * the number has none yet, and the caller may offer to open one.
     *
     * @throws OtpException
     */
    public function verify(string $phone, string $code): ?User
    {
        $otp = PhoneOtp::query()
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp || $otp->expires_at->isPast() || $otp->attempts >= self::MAX_ATTEMPTS) {
            throw OtpException::expired();
        }

        if (! hash_equals($otp->code_hash, $this->hash($phone, $code))) {
            $otp->increment('attempts');

            throw $otp->attempts >= self::MAX_ATTEMPTS
                ? OtpException::expired()
                : OtpException::wrongCode();
        }

        $otp->update(['consumed_at' => now()]);

        $accounts = $this->accountsFor($phone);

        if ($accounts->count() > 1) {
            // A ketua RT may hold a warga account and a pengurus account on
            // one number. Picking either would sign them in as the wrong
            // person; the pengurus has to merge or re-number them.
            Log::notice('WhatsApp OTP matched several accounts', ['count' => $accounts->count()]);

            throw OtpException::ambiguous();
        }

        return $accounts->first();
    }

    /**
     * @return Collection<int, User>
     */
    public function accountsFor(string $phone): Collection
    {
        // A closed account is not an account: its owner deleted it.
        return User::query()
            ->where('phone_normalized', $phone)
            ->whereNull('closed_at')
            ->limit(2)
            ->get();
    }

    protected function hash(string $phone, string $code): string
    {
        return hash_hmac('sha256', $phone . '|' . $code, (string) config('app.key'));
    }
}
