<?php

namespace App\Models\Qurbans;

use App\Models\Organizations\Organization;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QurbanProgram extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = ['id'];

    protected $casts = [
        'year' => 'integer',
        'period_start_at' => 'datetime',
        'period_end_at' => 'datetime',
        'is_public' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(QurbanProgramPackage::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(QurbanOrder::class);
    }

    public function animalAllocations(): HasMany
    {
        return $this->hasMany(QurbanAnimalAllocation::class);
    }

    public function workflowLogs(): HasMany
    {
        return $this->hasMany(QurbanWorkflowLog::class);
    }

    public function distributionBatches(): HasMany
    {
        return $this->hasMany(QurbanDistributionBatch::class);
    }
}
