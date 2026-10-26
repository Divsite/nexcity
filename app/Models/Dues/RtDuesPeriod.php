<?php

namespace App\Models\Dues;

use App\Models\Organizations\Organization;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One collection point: a month of a monthly scheme, or the single date of a
 * one-off.
 */
class RtDuesPeriod extends Model
{
    use HasFactory;

    public const MONTHS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(RtDuesScheme::class, 'rt_dues_scheme_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(RtDuesBill::class, 'rt_dues_period_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * What a resident sees: "Agustus 2026", or the RT's own wording for a
     * one-off ("Iuran HUT RI").
     */
    public function getLabelAttribute(): string
    {
        if ($this->attributes['label'] ?? null) {
            return $this->attributes['label'];
        }

        return $this->month
            ? self::MONTHS[$this->month] . ' ' . $this->year
            : (string) $this->year;
    }
}
