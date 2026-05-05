<?php

namespace App\Models\Qurbans;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QurbanOrderItem extends Model
{
    use HasFactory;

    public const STATUS_RESERVED = 'reserved';
    public const STATUS_ALLOCATED = 'allocated';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = ['id'];

    protected $casts = [
        'qty' => 'integer',
        'share_qty' => 'integer',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(QurbanOrder::class, 'qurban_order_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(QurbanProgramPackage::class, 'qurban_program_package_id');
    }

    public function animalAllocations(): HasMany
    {
        return $this->hasMany(QurbanAnimalAllocation::class);
    }
}
