<?php

namespace App\Http\Controllers\FormSubmissionFiles;

use App\Http\Controllers\Controller;
use App\Models\FormSubmissionFiles\FormSubmissionFile;
use Illuminate\Support\Facades\Storage;

class FormSubmissionFileController extends Controller
{
    public function show(string $id)
    {
        $model = FormSubmissionFile::with('submission')->findOrFail($id);

        $file = FormSubmissionFile::FILE_PATH.$model->submission->id.'/'.$model->name;

        if (!Storage::exists($file)) {
            abort(404);
        }

        $filePath = storage_path('app/'.$file);

        return response()->file($filePath);
    }
}
