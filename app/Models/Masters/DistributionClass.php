<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistributionClass extends Model
{
    use HasFactory;

    protected $table = 'm_distribution_classes';

    protected $guarded = ['id'];
}
