<?php

namespace App\Models\Charities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharityMalReceipt extends Model
{
    use HasFactory;

    protected $table = 'charity_mal_receipts';

    protected $guarded = ['id'];
}
