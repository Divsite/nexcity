<?php

namespace App\Models\Qurbans;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QurbanAnimalAllocation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'share_index' => 'integer',
        'allocated_weight' => 'decimal:2',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(QurbanAnimal::class, 'qurban_animal_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(QurbanOrderItem::class, 'qurban_order_item_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(QurbanProgram::class, 'qurban_program_id');
    }
}
