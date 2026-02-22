<?php

namespace App\Models\CharityTypes;

use App\Models\CharityTypeSources\CharityTypeSource;
use App\Models\Organizations\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharityType extends Model
{
    use HasFactory;

    protected $table = 'charity_types';

    protected $guarded = ['id'];

    public function source(): BelongsTo
    {
        return $this->belongsTo(CharityTypeSource::class, 'charity_type_source_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
