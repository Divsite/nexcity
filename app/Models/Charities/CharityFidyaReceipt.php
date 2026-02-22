<?php

namespace App\Models\Charities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharityFidyaReceipt extends Model
{
    use HasFactory;

    protected $table = 'charity_fidya_receipts';

    protected $guarded = ['id'];
}
