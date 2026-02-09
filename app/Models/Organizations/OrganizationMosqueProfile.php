<?php

namespace App\Models\Organizations;

use App\Models\Masters\OwnershipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationMosqueProfile extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function ownershipStatus(): BelongsTo
    {
        return $this->belongsTo(OwnershipStatus::class);
    }
}
