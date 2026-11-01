<?php

namespace App\Models\Profiles;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A warga's request to correct their own resident data. See the migration
 * for why the warga never edits the record directly.
 */
class ResidentDataCorrection extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPLIED = 'applied';

    public const STATUS_REJECTED = 'rejected';

    /**
     * What a warga may ask to change, with the label they see.
     *
     * NIK is not here on purpose: it never travels to the app, so there is no
     * screen on which a warga could notice it is wrong.
     */
    public const FIELDS = [
        'name' => 'Nama',
        'phone' => 'Nomor HP',
        'address_line' => 'Alamat',
        'family_card_number' => 'Nomor KK',
        'is_head_family' => 'Kepala keluarga',
        'family_members_count' => 'Jumlah anggota keluarga',
        'birth_date' => 'Tanggal lahir',
        'other' => 'Lainnya',
    ];

    protected $guarded = [];

    // Mirrors the column default, which a freshly created model does not read
    // back: without it the response to "kirim" said status null.
    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
