<?php

namespace App\Models\Users;

use App\Models\ActivityLogs\ActivityLog;
use App\Models\Events\Event;
use App\Models\Organizations\Organization;
use App\Models\Organizations\OrganizationUser;
use App\Models\Profiles\UserCorporateProfile;
use App\Models\Profiles\UserInstitutionProfile;
use App\Models\Profiles\UserMosqueProfile;
use App\Models\Profiles\UserResidentProfile;
use App\Models\Profiles\UserRtProfile;
use App\Models\Profiles\UserUmkmProfile;
use App\Models\Qurbans\QurbanAnimal;
use App\Models\Qurbans\QurbanCouponClaim;
use App\Models\Qurbans\QurbanDistributionBatch;
use App\Models\Qurbans\QurbanOrder;
use App\Models\Qurbans\QurbanOrderPayment;
use App\Models\Qurbans\QurbanProgram;
use App\Models\Qurbans\QurbanWorkflowLog;
use App\Notifications\Users\ResetPassword as ResetPasswordNotification;
use App\Notifications\Users\VerifyEmail as VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, AuthenticationLoggable;

    const AVATAR_PATH = 'uploads/avatar/';

    const VERIFIED = 1;
    const UNVERIFIED = 2;

    const AVATAR_INITIAL_NAME = 1;
    const AVATAR_NOT_INITIAL_NAME = 2;

    public $guarded = [];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleted(function (User $user) {
            // Authentication Log
            $user->authentications()->delete();

            // Activity Log
            ActivityLog::query()
                ->where('causer_type', User::class)
                ->where('causer_id', $user->id)
                ->delete();
        });
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'profile_completed' => 'boolean',
    ];

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        if (config('core.verify_enabled')) {
            $this->notify(new VerifyEmailNotification());
        }
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function residentProfile(): HasOne
    {
        return $this->hasOne(UserResidentProfile::class);
    }

    public function mosqueProfile(): HasOne
    {
        return $this->hasOne(UserMosqueProfile::class);
    }

    public function rtProfile(): HasOne
    {
        return $this->hasOne(UserRtProfile::class);
    }

    public function umkmProfile(): HasOne
    {
        return $this->hasOne(UserUmkmProfile::class);
    }

    public function corporateProfile(): HasOne
    {
        return $this->hasOne(UserCorporateProfile::class);
    }

    public function institutionProfile(): HasOne
    {
        return $this->hasOne(UserInstitutionProfile::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user')
            ->using(OrganizationUser::class)
            ->withPivot(['role', 'level_slug', 'is_primary', 'joined_at'])
            ->withTimestamps();
    }

    public function organizationMemberships(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    public function qurbanOrders(): HasMany
    {
        return $this->hasMany(QurbanOrder::class);
    }

    public function createdQurbanPrograms(): HasMany
    {
        return $this->hasMany(QurbanProgram::class, 'created_by');
    }

    public function createdQurbanOrders(): HasMany
    {
        return $this->hasMany(QurbanOrder::class, 'created_by');
    }

    public function receivedQurbanOrderPayments(): HasMany
    {
        return $this->hasMany(QurbanOrderPayment::class, 'received_by');
    }

    public function createdQurbanAnimals(): HasMany
    {
        return $this->hasMany(QurbanAnimal::class, 'created_by');
    }

    public function performedQurbanWorkflowLogs(): HasMany
    {
        return $this->hasMany(QurbanWorkflowLog::class, 'performed_by');
    }

    public function createdQurbanDistributionBatches(): HasMany
    {
        return $this->hasMany(QurbanDistributionBatch::class, 'created_by');
    }

    public function claimedQurbanCouponClaims(): HasMany
    {
        return $this->hasMany(QurbanCouponClaim::class, 'claimed_by_user_id');
    }

    public function scannedQurbanCouponClaims(): HasMany
    {
        return $this->hasMany(QurbanCouponClaim::class, 'scanner_user_id');
    }

    public function hostEvents()
    {
        return $this->belongsToMany(Event::class);
    }

    public function speakerEvents()
    {
        return $this->belongsToMany(Event::class);
    }
}
