<?php

namespace App\Models\Charities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharityEndowmentReceipt extends Model
{
    use HasFactory;

    protected $table = 'charity_endowment_receipts';

    protected $guarded = ['id'];
}
