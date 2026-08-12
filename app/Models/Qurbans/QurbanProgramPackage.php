<?php

namespace App\Models\Qurbans;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QurbanProgramPackage extends Model
{
    use HasFactory;

    public const ANIMAL_TYPE_COW = 'cow';
    public const ANIMAL_TYPE_GOAT = 'goat';
    public const ANIMAL_TYPE_SHEEP = 'sheep';

    public const PACKAGE_TYPE_FULL = 'full';
    public const PACKAGE_TYPE_SHARE = 'share';

    protected $guarded = ['id'];

    /**
     * The ceiling on what a mosque may charge for organising, as a share of the
     * vendor's price.
     *
     * A cap exists because without one the property that makes the split
     * worthwhile inverts: nobody can undercut on the animal, so the only way to
     * compete is on service — and the only way to profit is to charge more for
     * it. Ten per cent covers a jagal, plastic, ice and transport with room to
     * spare; beyond that it stops being a cost and starts being a margin.
     */
    public const MAX_SERVICE_FEE_RATIO = 0.10;

    protected $casts = [
        'share_count' => 'integer',
        'target_weight_min' => 'decimal:2',
        'target_weight_max' => 'decimal:2',
        'base_price' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'price' => 'decimal:2',
        'quota' => 'integer',
        'remaining_quota' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // The total is kept, not derived on read — every report and export
        // would otherwise re-implement the same sum and one would get it
        // wrong. Keeping it in sync belongs here, once.
        static::saving(function (self $package) {
            $package->price = (float) $package->base_price
                + (float) $package->service_fee;
        });
    }

    /** The most this mosque may charge on top of the vendor's price. */
    public function maxServiceFee(): float
    {
        return round((float) $this->base_price * self::MAX_SERVICE_FEE_RATIO, 2);
    }

    public function serviceFeeIsWithinCap(): bool
    {
        return (float) $this->service_fee <= $this->maxServiceFee();
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(QurbanProgram::class, 'qurban_program_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(QurbanOrderItem::class);
    }
}
