<?php

namespace App\Models\Organizations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLevelPermission extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function level(): BelongsTo
    {
        return $this->belongsTo(UserLevel::class, 'user_level_id');
    }
}
