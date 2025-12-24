<?php

namespace App\Http\Controllers\FormSubmissionFiles;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormSubmissionFiles\CreateTemporaryFileRequest;
use App\Models\FormSubmissionFiles\TemporaryFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemporaryFileController extends Controller
{
    public function store(CreateTemporaryFileRequest $request, $id, $uuid)
    {
        if ($request->hasFile('filepond')) {
            $file = $request->file('filepond');
            $fileName = $file->getClientOriginalName();
            $folder = Str::uuid().'-'.now()->timestamp;
            $file->storeAs(TemporaryFile::FILE_PATH.$folder, $fileName);

            TemporaryFile::create([
                'folder' => $folder,
                'filename' => $fileName
            ]);

            return response()->json(['folder' => $folder]);
        }

        return response()->json(['folder' => '']);
    }

    public function destroy(Request $request)
    {
        $id = $request->getContent();
        $model = TemporaryFile::where('folder', $id)->first();

        if ($model) {
            $folder = $model->folder;
            Storage::deleteDirectory(TemporaryFile::FILE_PATH.$folder);

            // Delete tmp data
            $model->delete();

            return response()->json(['folder' => $folder]);
        }

        return response()->json(['folder' => '']);
    }
}
