<?php

namespace App\Models\Distributions;

use App\Models\Locations\NeighborhoodAssociation;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionCoupon extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'claimed_at' => 'datetime',
    ];

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    public function neighborhoodAssociation(): BelongsTo
    {
        return $this->belongsTo(NeighborhoodAssociation::class);
    }

    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }
}
