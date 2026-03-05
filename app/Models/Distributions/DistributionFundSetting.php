<?php

namespace App\Models\Distributions;

use App\Models\Locations\NeighborhoodAssociation;
use App\Models\Organizations\Organization;
use App\Models\Users\User;
use App\Models\DistributionClasses\DistributionClass;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionFundSetting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'priority_charity_type_ids' => 'array',
        'enforce_priority' => 'boolean',
        'year' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function distributionClass(): BelongsTo
    {
        return $this->belongsTo(DistributionClass::class);
    }

    public function neighborhoodAssociation(): BelongsTo
    {
        return $this->belongsTo(NeighborhoodAssociation::class, 'neighborhood_association_id');
    }
}
