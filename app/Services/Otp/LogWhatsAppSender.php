<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Development driver: writes the message to the log instead of sending it.
 *
 * Refuses to run in production. A log driver left on there would accept every
 * login request and deliver nothing, and nobody would notice until warga
 * complained that the code never arrived.
 */
class LogWhatsAppSender implements WhatsAppSender
{
    public function send(string $phone, string $message): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('WHATSAPP_OTP_DRIVER=log is not allowed in production.');
        }

        Log::info('WhatsApp OTP (log driver)', ['phone' => $phone, 'message' => $message]);
    }
}
