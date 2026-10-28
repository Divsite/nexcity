<?php

namespace App\Models\Dues;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How much one golongan pays under a scheme.
 *
 * A null [tier] means everyone pays this — the ordinary shape for a one-off
 * collection. Adding golongan later is adding rows, not changing code.
 */
class RtDuesRate extends Model
{
    use HasFactory;

    /** Family card registered in this RT. On the printed card: Rp 20.000. */
    public const TIER_WITH_CARD = 'ber_kk';

    /** Living here without local papers — kontrakan, kos. Rp 15.000. */
    public const TIER_WITHOUT_CARD = 'tanpa_kk';

    /**
     * The two golongan the printed cards use. An RT is free to define others;
     * these are only what the "Iuran Bulanan" template starts with.
     */
    public const COMMON_TIERS = [
        self::TIER_WITH_CARD => 'Ber KK',
        self::TIER_WITHOUT_CARD => 'Tidak Ber KK',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(RtDuesScheme::class, 'rt_dues_scheme_id');
    }

    /** True when this rate applies to everyone regardless of golongan. */
    public function isFlat(): bool
    {
        return $this->tier === null;
    }
}
