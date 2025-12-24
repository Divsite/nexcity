<?php

namespace App\Models\FormProcesses;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormProcessRole extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function process(): BelongsTo
    {
        return $this->belongsTo(FormProcess::class, 'process_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user', 'id');
    }
}
