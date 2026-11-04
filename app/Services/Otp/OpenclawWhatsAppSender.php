<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Sends through the OpenClaw gateway that already delivers zakat
 * notifications to mosque WhatsApp groups (OpenclawWebhookService), addressed
 * to one person instead of a group.
 *
 * Unlike that service this never logs the payload: it contains a working
 * sign-in code.
 */
class OpenclawWhatsAppSender implements WhatsAppSender
{
    public function send(string $phone, string $message): void
    {
        $url = config('services.openclaw.webhook_url');
        $token = config('services.openclaw.webhook_token');

        if (empty($url) || empty($token)) {
            throw new RuntimeException('OpenClaw webhook is not configured.');
        }

        try {
            $response = Http::withToken($token)
                ->timeout(8)
                ->post($url, [
                    'message' => $message,
                    'deliver' => true,
                    'channel' => 'whatsapp',
                    // A personal chat JID, where the zakat notices use a group's.
                    'to' => $phone . '@s.whatsapp.net',
                    'name' => 'login-otp',
                ]);
        } catch (Throwable $exception) {
            Log::warning('WhatsApp OTP delivery failed', ['reason' => $exception->getMessage()]);

            throw new RuntimeException('WhatsApp OTP delivery failed.', previous: $exception);
        }

        if ($response->failed()) {
            Log::warning('WhatsApp OTP rejected by gateway', ['status' => $response->status()]);

            throw new RuntimeException('WhatsApp OTP rejected by gateway.');
        }
    }
}
