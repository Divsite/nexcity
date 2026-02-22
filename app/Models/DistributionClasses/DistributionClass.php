<?php

namespace App\Models\DistributionClasses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\DistributionClassSources\DistributionClassSource;
use App\Models\Organizations\Organization;

class DistributionClass extends Model
{
    use HasFactory;

    protected $table = 'distribution_classes';

    protected $guarded = ['id'];

    public function source(): BelongsTo
    {
        return $this->belongsTo(DistributionClassSource::class, 'distribution_class_source_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
