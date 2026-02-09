<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationMajor extends Model
{
    use HasFactory;

    protected $table = 'm_education_majors';

    protected $guarded = ['id'];
}
