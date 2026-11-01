<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Model;

/**
 * A WhatsApp sign-in code. See App\Services\Otp\OtpService.
 */
class PhoneOtp extends Model
{
    protected $guarded = [];

    protected $casts = [
        'attempts' => 'integer',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];
}
