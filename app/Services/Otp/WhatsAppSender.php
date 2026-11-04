<?php

namespace App\Services\Otp;

/**
 * Delivers a sign-in code to a phone over WhatsApp.
 *
 * Bound in AppServiceProvider from `services.whatsapp_otp.driver`, so the
 * provider can change without touching the login flow.
 */
interface WhatsAppSender
{
    /**
     * @param  string  $phone  normalized, e.g. 6281234567890
     *
     * @throws \RuntimeException when the message could not be handed over
     */
    public function send(string $phone, string $message): void;
}
