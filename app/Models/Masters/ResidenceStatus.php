<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidenceStatus extends Model
{
    use HasFactory;

    protected $table = 'm_residence_statuses';

    protected $guarded = ['id'];
}
