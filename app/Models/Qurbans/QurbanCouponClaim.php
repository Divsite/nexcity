<?php

namespace App\Models\Qurbans;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QurbanCouponClaim extends Model
{
    use HasFactory;

    public const RESULT_SUCCESS = 'success';
    public const RESULT_ALREADY_CLAIMED = 'already_claimed';
    public const RESULT_CANCELLED = 'cancelled';
    public const RESULT_EXPIRED = 'expired';
    public const RESULT_INVALID = 'invalid';

    protected $guarded = ['id'];

    protected $casts = [
        'claimed_at' => 'datetime',
        'scan_latitude' => 'decimal:7',
        'scan_longitude' => 'decimal:7',
        'meta' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(QurbanDistributionBatch::class, 'qurban_distribution_batch_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(QurbanCoupon::class, 'qurban_coupon_id');
    }

    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanner_user_id');
    }
}
