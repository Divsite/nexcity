<?php

namespace App\Models\Distributions;

use App\Models\Locations\NeighborhoodAssociation;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionOfficer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function neighborhoodAssociation(): BelongsTo
    {
        return $this->belongsTo(NeighborhoodAssociation::class);
    }
}
