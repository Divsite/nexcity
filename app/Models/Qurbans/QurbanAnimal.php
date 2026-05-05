<?php

namespace App\Models\Qurbans;

use App\Models\Organizations\Organization;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QurbanAnimal extends Model
{
    use HasFactory;

    public const TYPE_COW = 'cow';
    public const TYPE_GOAT = 'goat';
    public const TYPE_SHEEP = 'sheep';

    public const GENDER_MALE = 'male';
    public const GENDER_FEMALE = 'female';

    public const HEALTH_STATUS_HEALTHY = 'healthy';
    public const HEALTH_STATUS_OBSERVATION = 'observation';
    public const HEALTH_STATUS_REJECTED = 'rejected';

    public const PROCUREMENT_TYPE_PURCHASED = 'purchased';
    public const PROCUREMENT_TYPE_PARTNER = 'partner';
    public const PROCUREMENT_TYPE_DONATED = 'donated';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'ready';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_SLAUGHTERED = 'slaughtered';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_DISTRIBUTED = 'distributed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = ['id'];

    protected $casts = [
        'weight' => 'decimal:2',
        'estimated_meat_weight' => 'decimal:2',
        'age_months' => 'integer',
        'purchase_price' => 'decimal:2',
        'purchase_date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(QurbanAnimalAllocation::class);
    }

    public function workflowLogs(): HasMany
    {
        return $this->hasMany(QurbanWorkflowLog::class);
    }
}
