<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnershipStatus extends Model
{
    use HasFactory;

    protected $table = 'm_ownership_statuses';

    protected $guarded = ['id'];
}
