<?php

namespace App\Models\FormProcesses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormProcessAction extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_revert_submitter' => 'boolean',
        'is_end_process' => 'boolean',
        'comment_required' => 'boolean',
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(FormProcess::class, 'process_id', 'id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(FormProcessStatus::class, 'status_id', 'id');
    }

    public function linkedProcess(): BelongsTo
    {
        return $this->belongsTo(FormProcess::class, 'linked_process_id', 'id');
    }
}
