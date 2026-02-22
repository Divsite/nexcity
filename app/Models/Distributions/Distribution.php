<?php

namespace App\Models\Distributions;

use App\Models\Locations\CitizensAssociation;
use App\Models\Locations\City;
use App\Models\Locations\Country;
use App\Models\Locations\District;
use App\Models\Locations\NeighborhoodAssociation;
use App\Models\Locations\Province;
use App\Models\Locations\Village;
use App\Models\DistributionTypes\DistributionType;
use App\Models\Organizations\Organization;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Distribution extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DistributionType::class, 'distribution_type_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function citizensAssociation(): BelongsTo
    {
        return $this->belongsTo(CitizensAssociation::class);
    }

    public function neighborhoodAssociation(): BelongsTo
    {
        return $this->belongsTo(NeighborhoodAssociation::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(DistributionRecipient::class);
    }

    public function officers(): HasMany
    {
        return $this->hasMany(DistributionOfficer::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(DistributionCoupon::class);
    }
}
