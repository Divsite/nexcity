<?php

namespace App\Models\FormSubmissionFiles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporaryFile extends Model
{
    use HasFactory;

    const FILE_PATH = 'form-submission-files/tmp/';

    protected $guarded = ['id'];

    protected $table = "form_submission_temporary_files";
}
