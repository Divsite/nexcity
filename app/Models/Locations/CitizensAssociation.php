<?php

namespace App\Models\Locations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CitizensAssociation extends Model
{
    use HasFactory;

    protected $table = 'loc_citizens_associations';

    protected $guarded = ['id'];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function neighborhoodAssociations(): HasMany
    {
        return $this->hasMany(NeighborhoodAssociation::class);
    }
}
