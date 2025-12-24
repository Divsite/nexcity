<?php

namespace App\Models\FormSubmissionFiles;

use App\Models\FormSubmissions\FormSubmission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmissionFile extends Model
{
    use HasFactory;

    const FILE_PATH = 'form-submission-files/uploads/';

    protected $guarded = ['id'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id', 'id');
    }
}
