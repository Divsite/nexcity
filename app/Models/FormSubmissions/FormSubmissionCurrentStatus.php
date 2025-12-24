<?php

namespace App\Models\FormSubmissions;

use App\Models\FormProcesses\FormProcess;
use App\Models\FormProcesses\FormProcessStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmissionCurrentStatus extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_revert_submitter' => 'boolean',
        'is_end_process' => 'boolean',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id', 'id');
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(FormProcess::class, 'process_id', 'id')->withTrashed();
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(FormProcessStatus::class, 'status_id', 'id')->withTrashed();
    }
}
