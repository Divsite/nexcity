<?php

namespace App\Models\Organizations;

use App\Models\Locations\CitizensAssociation;
use App\Models\Locations\City;
use App\Models\Locations\Country;
use App\Models\Locations\District;
use App\Models\Locations\NeighborhoodAssociation;
use App\Models\Locations\Province;
use App\Models\Locations\Village;
use App\Models\Menus\UserMenu;
use App\Models\Qurbans\QurbanAnimal;
use App\Models\Qurbans\QurbanBeneficiary;
use App\Models\Qurbans\QurbanDistributionBatch;
use App\Models\Qurbans\QurbanOrder;
use App\Models\Qurbans\QurbanProgram;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory;

    public const TYPE_RT = 'rt';
    public const TYPE_MOSQUE = 'mosque';
    public const TYPE_UMKM = 'umkm';
    public const TYPE_INSTITUTION = 'institution';

    protected $guarded = ['id'];

    protected $casts = [
        'settings' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Organization $organization) {
            if (! $organization->uuid) {
                $organization->uuid = (string) Str::uuid();
            }

            if (! $organization->slug) {
                $organization->slug = Str::slug($organization->name);
            }
        });
    }

    public function profile(): HasOne
    {
        return $this->hasOne(OrganizationProfile::class);
    }

    public function mosqueProfile(): HasOne
    {
        return $this->hasOne(OrganizationMosqueProfile::class);
    }

    public function rtProfile(): HasOne
    {
        return $this->hasOne(OrganizationRtProfile::class);
    }

    public function umkmProfile(): HasOne
    {
        return $this->hasOne(OrganizationUmkmProfile::class);
    }

    public function corporateProfile(): HasOne
    {
        return $this->hasOne(OrganizationCorporateProfile::class);
    }

    public function institutionProfile(): HasOne
    {
        return $this->hasOne(OrganizationInstitutionProfile::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(OrganizationCategory::class, 'organization_category_id');
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

    public function members(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->using(OrganizationUser::class)
            ->withPivot(['role', 'level_slug', 'is_primary', 'joined_at'])
            ->withTimestamps();
    }

    public function levels(): HasMany
    {
        return $this->hasMany(UserLevel::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(UserMenu::class);
    }

    public function whatsappGroups(): HasMany
    {
        return $this->hasMany(OrganizationWhatsappGroup::class);
    }

    public function qurbanPrograms(): HasMany
    {
        return $this->hasMany(QurbanProgram::class);
    }

    public function qurbanOrders(): HasMany
    {
        return $this->hasMany(QurbanOrder::class);
    }

    public function qurbanAnimals(): HasMany
    {
        return $this->hasMany(QurbanAnimal::class);
    }

    public function qurbanDistributionBatches(): HasMany
    {
        return $this->hasMany(QurbanDistributionBatch::class);
    }

    public function qurbanBeneficiaries(): HasMany
    {
        return $this->hasMany(QurbanBeneficiary::class);
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_RT => 'RT / RW',
            self::TYPE_MOSQUE => 'Mosque',
            self::TYPE_UMKM => 'UMKM',
            self::TYPE_INSTITUTION => 'Institution',
        ];
    }
}
