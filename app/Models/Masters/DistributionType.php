<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistributionType extends Model
{
    use HasFactory;

    protected $table = 'm_distribution_types';

    protected $guarded = ['id'];
}
