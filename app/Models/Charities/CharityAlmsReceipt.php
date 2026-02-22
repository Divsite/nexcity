<?php

namespace App\Models\Charities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharityAlmsReceipt extends Model
{
    use HasFactory;

    protected $table = 'charity_alms_receipts';

    protected $guarded = ['id'];
}
