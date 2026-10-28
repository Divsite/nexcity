<?php

namespace App\Models\Dues;

use App\Models\Organizations\Organization;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * What an RT is collecting, and what it funds.
 *
 * Monthly dues are one scheme. "Iuran HUT RI 2026" is another. Every RT invents
 * its own set, which is why this is a row and not an enum in code.
 */
class RtDuesScheme extends Model
{
    use HasFactory;

    /** Twelve periods a year, like the printed card. */
    public const TYPE_MONTHLY = 'monthly';

    /** One collection, on a date the RT picks: HUT RI, a renovation fund. */
    public const TYPE_SEASONAL = 'seasonal';

    public const TYPES = [self::TYPE_MONTHLY, self::TYPE_SEASONAL];

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(RtDuesRate::class, 'rt_dues_scheme_id');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(RtDuesPeriod::class, 'rt_dues_scheme_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isMonthly(): bool
    {
        return $this->type === self::TYPE_MONTHLY;
    }

    /**
     * The programmes this pays for, one per line as the treasurer typed them.
     *
     * @return list<string>
     */
    public function programList(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->programs))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
