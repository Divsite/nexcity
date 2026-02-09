<?php

namespace App\Models\Locations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NeighborhoodAssociation extends Model
{
    use HasFactory;

    protected $table = 'loc_neighborhood_associations';

    protected $guarded = ['id'];

    public function citizensAssociation(): BelongsTo
    {
        return $this->belongsTo(CitizensAssociation::class);
    }
}
