<?php

namespace App\Models\Distributions;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionRecipientStatusLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'reschedule_at' => 'datetime',
        'redirect_money' => 'float',
        'redirect_rice' => 'float',
    ];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(DistributionRecipient::class, 'distribution_recipient_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
