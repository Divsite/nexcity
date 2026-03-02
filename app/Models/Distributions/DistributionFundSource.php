<?php

namespace App\Models\Distributions;

use App\Models\CharityTypes\CharityType;
use App\Models\DistributionClasses\DistributionClass;
use App\Models\DistributionTypes\DistributionType;
use App\Models\Locations\NeighborhoodAssociation;
use App\Models\Organizations\Organization;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionFundSource extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function distributionType(): BelongsTo
    {
        return $this->belongsTo(DistributionType::class);
    }

    public function distributionClass(): BelongsTo
    {
        return $this->belongsTo(DistributionClass::class);
    }

    public function neighborhoodAssociation(): BelongsTo
    {
        return $this->belongsTo(NeighborhoodAssociation::class);
    }

    public function charityType(): BelongsTo
    {
        return $this->belongsTo(CharityType::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
