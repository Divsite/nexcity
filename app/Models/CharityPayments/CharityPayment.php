<?php

namespace App\Models\CharityPayments;

use App\Models\Masters\Bank;
use App\Models\Organizations\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharityPayment extends Model
{
    use HasFactory;

    protected $table = 'charity_payments';

    protected $guarded = ['id'];

    public const QRIS_PATH = 'charity-payments/qris';

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }
}
