<?php

namespace App\Models\Charities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharityDonationReceipt extends Model
{
    use HasFactory;

    protected $table = 'charity_donation_receipts';

    protected $guarded = ['id'];
}
