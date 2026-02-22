<?php

namespace App\Models\DistributionClassSources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\DistributionClasses\DistributionClass;

class DistributionClassSource extends Model
{
    use HasFactory;

    protected $table = 'm_distribution_class_sources';

    protected $guarded = ['id'];

    public function classes(): HasMany
    {
        return $this->hasMany(DistributionClass::class, 'distribution_class_source_id');
    }
}
