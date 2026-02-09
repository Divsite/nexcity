<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidentStatus extends Model
{
    use HasFactory;

    protected $table = 'm_resident_statuses';

    protected $guarded = ['id'];
}
