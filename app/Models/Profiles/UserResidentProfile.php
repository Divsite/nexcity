<?php

namespace App\Models\Profiles;

use App\Models\Locations\CitizensAssociation;
use App\Models\Locations\City;
use App\Models\Locations\Country;
use App\Models\Locations\District;
use App\Models\Locations\NeighborhoodAssociation;
use App\Models\Locations\Province;
use App\Models\Locations\Village;
use App\Models\Masters\Education;
use App\Models\Masters\EducationMajor;
use App\Models\Masters\MaritalStatus;
use App\Models\Masters\Religion;
use App\Models\Masters\ResidenceStatus;
use App\Models\Organizations\Organization;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UserResidentProfile extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'birth_date' => 'date',
        'qr_generated_at' => 'datetime',
        'additional_info' => 'array',
        'interests' => 'array',
        'talents' => 'array',
        'house_photo_paths' => 'array',
        'is_head_family' => 'boolean',
        'family_members_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (UserResidentProfile $profile) {
            if (! $profile->qr_token) {
                $profile->qr_token = (string) Str::uuid();
                $profile->qr_generated_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function residenceStatus(): BelongsTo
    {
        return $this->belongsTo(ResidenceStatus::class);
    }

    public function maritalStatus(): BelongsTo
    {
        return $this->belongsTo(MaritalStatus::class);
    }

    public function education(): BelongsTo
    {
        return $this->belongsTo(Education::class);
    }

    public function educationMajor(): BelongsTo
    {
        return $this->belongsTo(EducationMajor::class);
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
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
}
