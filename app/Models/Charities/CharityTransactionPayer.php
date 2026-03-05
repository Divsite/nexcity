<?php

namespace App\Models\Charities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharityTransactionPayer extends Model
{
    use HasFactory;

    protected $table = 'charity_transaction_payers';

    protected $guarded = ['id'];

    protected $casts = [
        'is_money' => 'boolean',
        'is_rice' => 'boolean',
        'multiplier_count' => 'integer',
        'total_money' => 'float',
        'total_rice' => 'float',
    ];

    public function charityTransaction(): BelongsTo
    {
        return $this->belongsTo(CharityTransaction::class, 'charity_transaction_id');
    }
}
