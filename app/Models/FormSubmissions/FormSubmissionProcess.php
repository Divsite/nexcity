<?php

namespace App\Models\FormSubmissions;

use App\Models\FormProcesses\FormProcess;
use App\Models\FormProcesses\FormProcessStatus;
use App\Models\Users\User;
use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmissionProcess extends Model
{
    use HasFactory, CreatedUpdatedBy;

    protected $guarded = [];

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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
