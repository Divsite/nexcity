<?php

namespace App\Models\Charities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharityFitrahReceipt extends Model
{
    use HasFactory;

    protected $table = 'charity_fitrah_receipts';

    protected $guarded = ['id'];
}
