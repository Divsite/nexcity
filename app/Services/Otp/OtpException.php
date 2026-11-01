<?php

namespace App\Services\Otp;

use RuntimeException;

/**
 * Why a WhatsApp code did not sign anyone in. The message is shown to the
 * warga as is.
 */
class OtpException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }

    public static function wrongCode(): self
    {
        return new self('Kode salah. Periksa lagi pesan WhatsApp Anda.', 422);
    }

    public static function expired(): self
    {
        return new self('Kode sudah tidak berlaku. Minta kode baru.', 422);
    }

    public static function ambiguous(): self
    {
        return new self(
            'Nomor ini dipakai lebih dari satu akun. Hubungi pengurus RT atau masjid untuk merapikannya.',
            409,
        );
    }
}
