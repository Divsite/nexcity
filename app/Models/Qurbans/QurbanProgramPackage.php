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

    protected $casts = [
        'share_count' => 'integer',
        'target_weight_min' => 'decimal:2',
        'target_weight_max' => 'decimal:2',
        'price' => 'decimal:2',
        'quota' => 'integer',
        'remaining_quota' => 'integer',
        'is_active' => 'boolean',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(QurbanProgram::class, 'qurban_program_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(QurbanOrderItem::class);
    }
}
