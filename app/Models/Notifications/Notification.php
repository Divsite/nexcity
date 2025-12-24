<?php

namespace App\Models\Notifications;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    const HAS_READ = 1;
    const NOT_READ = 2;

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'data' => 'array',
    ];
}
