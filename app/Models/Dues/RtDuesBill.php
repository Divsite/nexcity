<?php

namespace App\Models\Dues;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What one household owes for one period.
 */
class RtDuesBill extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';

    /** Hardship, forgiven by the RT. Not the same as paid, and the books
     *  should not pretend otherwise. */
    public const STATUS_WAIVED = 'waived';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_PAID, self::STATUS_WAIVED];

    protected $guarded = ['id'];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(RtDuesPeriod::class, 'rt_dues_period_id');
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resident_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** Only a pending bill counts as owed; a waived one does not. */
    public function isOutstanding(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /** The golongan this was billed at, in the RT's own words. */
    public function tierLabel(): ?string
    {
        return $this->tier === null
            ? null
            : (RtDuesRate::COMMON_TIERS[$this->tier] ?? $this->tier);
    }
}
