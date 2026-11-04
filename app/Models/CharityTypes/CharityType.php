<?php

namespace App\Models\CharityTypes;

use App\Models\Charities\CharityTransaction;
use App\Models\CharityTypeSources\CharityTypeSource;
use App\Models\Organizations\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharityType extends Model
{
    use HasFactory;

    protected $table = 'charity_types';

    protected $guarded = ['id'];

    public function source(): BelongsTo
    {
        return $this->belongsTo(CharityTypeSource::class, 'charity_type_source_id');
    }

    /**
     * Every transaction booked against this type.
     *
     * Used to count what a filter chip will show. A chip with no count reads
     * as a label, and an officer cannot tell three transactions from three
     * hundred.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CharityTransaction::class, 'charity_type_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
