<?php

namespace App\Models\Distributions;

use App\Models\DistributionClasses\DistributionClass;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistributionRecipient extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'distributed_at' => 'datetime',
        'reschedule_at' => 'datetime',
    ];

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resident_id');
    }

    public function distributionClass(): BelongsTo
    {
        return $this->belongsTo(DistributionClass::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DistributionRecipientAttachment::class, 'distribution_recipient_id');
    }
}
