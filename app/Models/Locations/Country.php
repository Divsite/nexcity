<?php

namespace App\Models\Locations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $table = 'loc_countries';

    protected $guarded = ['id'];

    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class);
    }
}
