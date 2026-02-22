<?php

namespace App\Models\CharityTypeSources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CharityTypes\CharityType;

class CharityTypeSource extends Model
{
    use HasFactory;

    protected $table = 'm_charity_type_sources';

    protected $guarded = ['id'];

    public function charityTypes(): HasMany
    {
        return $this->hasMany(CharityType::class, 'charity_type_source_id');
    }
}
