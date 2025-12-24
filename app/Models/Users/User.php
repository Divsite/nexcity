<?php

namespace App\Models\Users;

use App\Models\ActivityLogs\ActivityLog;
use App\Models\Events\Event;
use App\Notifications\Users\ResetPassword as ResetPasswordNotification;
use App\Notifications\Users\VerifyEmail as VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use ReflectionClass;
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

    protected $with = ['profile'];

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

    public function profile(): HasOne
    {
        $profileClass = new ReflectionClass(str_replace('::class', '', config('models.profile')));
        return $this->hasOne($profileClass->getName(), 'user_id', 'id');
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
