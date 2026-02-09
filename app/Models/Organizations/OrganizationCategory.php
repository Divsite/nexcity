<?php

namespace App\Models\Organizations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationCategory extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }
}
