<?php

namespace App\Models\Qurbans;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QurbanCoupon extends Model
{
    use HasFactory;

    public const STATUS_ISSUED = 'issued';
    public const STATUS_CLAIMED = 'claimed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    protected $guarded = ['id'];

    protected $casts = [
        'meat_weight' => 'decimal:2',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(QurbanDistributionBatch::class, 'qurban_distribution_batch_id');
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(QurbanBeneficiary::class, 'qurban_beneficiary_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(QurbanCouponClaim::class);
    }
}
