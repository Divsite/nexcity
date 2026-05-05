<?php

namespace App\Models\Qurbans;

use App\Models\Locations\CitizensAssociation;
use App\Models\Locations\NeighborhoodAssociation;
use App\Models\Organizations\Organization;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QurbanBeneficiary extends Model
{
    use HasFactory;

    public const CATEGORY_RESIDENT = 'resident';
    public const CATEGORY_MANUAL = 'manual';
    public const CATEGORY_INTERNAL = 'internal';
    public const CATEGORY_PUBLIC = 'public';

    protected $guarded = ['id'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resident_id');
    }

    public function citizensAssociation(): BelongsTo
    {
        return $this->belongsTo(CitizensAssociation::class);
    }

    public function neighborhoodAssociation(): BelongsTo
    {
        return $this->belongsTo(NeighborhoodAssociation::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(QurbanCoupon::class);
    }
}
