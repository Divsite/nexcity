<?php

namespace App\Models\Profiles;

use App\Models\Organizations\Organization;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCorporateProfile extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'service_start_date' => 'date',
        'service_end_date' => 'date',
        'additional_info' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
