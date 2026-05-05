<?php

namespace App\Models\Qurbans;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QurbanWorkflowLog extends Model
{
    use HasFactory;

    public const STAGE_ORDER_CREATED = 'order_created';
    public const STAGE_PAYMENT_CONFIRMED = 'payment_confirmed';
    public const STAGE_ANIMAL_ASSIGNED = 'animal_assigned';
    public const STAGE_DEPARTED = 'departed';
    public const STAGE_ARRIVED = 'arrived';
    public const STAGE_SLAUGHTERED = 'slaughtered';
    public const STAGE_SKINNING = 'skinning';
    public const STAGE_CUTTING = 'cutting';
    public const STAGE_PACKING = 'packing';
    public const STAGE_DISTRIBUTING = 'distributing';
    public const STAGE_COMPLETED = 'completed';

    protected $guarded = ['id'];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(QurbanProgram::class, 'qurban_program_id');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(QurbanAnimal::class, 'qurban_animal_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(QurbanOrder::class, 'qurban_order_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
