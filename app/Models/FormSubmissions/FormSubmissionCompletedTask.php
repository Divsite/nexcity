<?php

namespace App\Models\FormSubmissions;

use App\Models\FormProcesses\FormProcess;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmissionCompletedTask extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(FormProcess::class, 'process_id', 'id');
    }
}
