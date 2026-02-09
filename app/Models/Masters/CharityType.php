<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharityType extends Model
{
    use HasFactory;

    protected $table = 'm_charity_types';

    protected $guarded = ['id'];
}
